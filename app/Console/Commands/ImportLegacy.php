<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\Site;
use App\Models\Warehouse;
use App\Services\ImportService;
use App\Support\SpreadsheetReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * PART 38 — CLI legacy import with dry-run (NO WRITE):
 * php artisan import:legacy --file=... --type=sparepart_master --sheet="MASTER"
 *   --company=1 --site=1 --warehouse=1 [--dry-run] [--force]
 */
class ImportLegacy extends Command
{
    protected $signature = 'import:legacy
        {--file= : Path to file (csv/xlsx/xls)}
        {--type= : Import type (sparepart_master, opening_stock, letter_register, legacy_invoice, legacy_receipt)}
        {--sheet= : Sheet name for workbook files}
        {--company= : Company ID scope for imported rows}
        {--site= : Site ID scope}
        {--warehouse= : Warehouse ID (opening_stock override)}
        {--mode= : Import mode (REGISTER_ONLY, OPENING_AR, HISTORY_ONLY, OPENING_PAYMENT, STOCK_ONLY, STOCK_AND_ACCOUNTING)}
        {--dry-run : Validate and preview only — no write}
        {--force : Re-import even when the same file content was imported before}';

    protected $description = 'Import legacy spreadsheet (native xlsx/xls/csv) with auto mapping, dry-run and fingerprint dedup';

    public function handle(): int
    {
        $file = (string) $this->option('file');
        $type = (string) $this->option('type');
        if ($file === '' || ! file_exists($file)) {
            $this->error('File tidak ditemukan: '.$file);

            return self::FAILURE;
        }
        if (! array_key_exists($type, ImportService::types())) {
            $this->error('Tipe tidak dikenal. Pilihan: '.implode(', ', array_keys(ImportService::types())));

            return self::FAILURE;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (! in_array($ext, SpreadsheetReader::SUPPORTED, true)) {
            $this->error("Format .{$ext} tidak didukung. Gunakan csv/xlsx/xls.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        // dry-run: parse + validate WITHOUT creating a batch row (no write at all)
        if ($dryRun) {
            return $this->dryRun($file, $type);
        }

        $sheets = SpreadsheetReader::sheets($file);
        $sheet = $this->option('sheet');
        if ($sheet !== null && $sheets !== ['Sheet1'] && ! in_array($sheet, $sheets, true)) {
            $this->error("Sheet '{$sheet}' tidak ada. Sheets: ".implode(', ', $sheets));

            return self::FAILURE;
        }

        $stored = 'imports/cli-'.uniqid().'.'.$ext;
        Storage::disk('local')->put($stored, file_get_contents($file));

        $batch = ImportBatch::create([
            'type' => $type,
            'file_name' => $stored,
            'file_hash' => hash_file('sha256', $file) ?: '',
            'file_ext' => $ext,
            'sheet' => $sheet,
            'mode' => $this->option('mode') ?: (ImportService::DEFAULT_MODES[$type] ?? null),
            'company_id' => $this->option('company') ? (int) $this->option('company') : Company::first()?->id,
            'site_id' => $this->option('site') ? (int) $this->option('site') : null,
            'warehouse_id' => $this->option('warehouse') ? (int) $this->option('warehouse') : null,
            'status' => 'UPLOADED',
            'force_import' => (bool) $this->option('force'),
        ]);

        $data = ImportService::readWorkbook($batch);
        if ($data['headers'] === []) {
            $this->error('Header tidak terbaca dari file.');

            return self::FAILURE;
        }
        $map = ImportService::autoMap($data['headers'], $type);
        $unmapped = collect($map)->filter(fn ($f) => $f === null)->keys()->all();
        $batch->update(['column_map' => $map, 'status' => 'MAPPED']);

        $result = ImportService::validate($batch, $map);
        $this->info('Auto-map: '.collect($map)->filter()->map(fn ($f, $h) => "{$h} → {$f}")->implode(', '));
        if ($unmapped !== []) {
            $this->warn('Kolom tidak dikenali (diabaikan): '.implode(', ', $unmapped));
        }
        $this->line("Baris: {$result['total']} · valid {$result['valid']} · warning {$result['warnings']} · duplikat ".($result['duplicates'] ?? 0)." · error {$result['failed']}");
        foreach (array_slice($result['errors'], 0, 10) as $e) {
            $this->warn("  baris {$e['row']}: {$e['error']}");
        }
        if ($result['failed'] > 0) {
            $this->error('Validasi gagal — import dibatalkan.');

            return self::FAILURE;
        }

        [$imported, $skipped] = ImportService::execute($batch, dryRun: false);
        $this->info("Import selesai: {$imported} masuk, {$skipped} duplikat dilewati. Batch #{$batch->id}");

        return self::SUCCESS;
    }

    private function dryRun(string $file, string $type): int
    {
        // no batch row, no write — parse in-memory
        $tmp = new ImportBatch(['type' => $type, 'file_name' => $file]);
        $data = ImportService::readWorkbook($tmp);
        if ($data['headers'] === []) {
            $this->error('Header tidak terbaca dari file.');

            return self::FAILURE;
        }
        $map = ImportService::autoMap($data['headers'], $type);
        $this->info('Auto-map: '.collect($map)->filter()->map(fn ($f, $h) => "{$h} → {$f}")->implode(', '));

        // fingerprint dedup needs a persisted batch id — skip in dry-run by
        // validating the mapped rows only
        $def = ImportService::types()[$type];
        $failed = 0;
        $valid = 0;
        $warnings = 0;
        $this->line(str_repeat('-', 100));
        $this->line(sprintf('%-6s %-30s %-30s %s', 'ROW', 'SOURCE', 'NORMALIZED', 'STATUS'));
        $this->line(str_repeat('-', 100));
        foreach (array_slice($data['rows'], 0, 50) as $i => $row) {
            $mapped = [];
            foreach ($map as $header => $field) {
                if ($field && array_key_exists($header, $row)) {
                    $mapped[$field] = trim((string) $row[$header]);
                }
            }
            $errors = [];
            foreach ($def['required'] as $req) {
                if (($mapped[$req] ?? '') === '') {
                    $errors[] = "kolom {$req} kosong";
                }
            }
            foreach (['qty', 'cost', 'total', 'amount'] as $num) {
                if (isset($mapped[$num]) && $mapped[$num] !== '' && ImportService::parseNumber($mapped[$num]) === null) {
                    $errors[] = "{$num} bukan angka valid";
                }
            }
            if (isset($mapped['date']) && $mapped['date'] !== '') {
                $parsed = ImportService::parseDateWithWarning($mapped['date']);
                if ($parsed === null) {
                    $errors[] = 'tanggal tidak valid';
                }
            }
            $status = $errors === [] ? '<fg=green>VALID</>' : '<fg=red>ERROR</>';
            if ($errors === [] && isset($mapped['date']) && ImportService::parseDateWithWarning($mapped['date'])['ambiguous'] ?? false) {
                $status = '<fg=yellow>WARNING</> (tanggal ambigu)';
                $warnings++;
            }
            if ($errors !== []) {
                $failed++;
            } else {
                $valid++;
            }
            $this->line(sprintf(
                '%-6d %-30s %-30s %s',
                $i + 2,
                mb_substr(implode(' | ', array_slice(array_values($row), 0, 3)), 0, 30),
                mb_substr(implode(' | ', array_slice(array_values($mapped), 0, 3)), 0, 30),
                $status.($errors !== [] ? ' — '.implode('; ', $errors) : '')
            ));
        }
        $this->line(str_repeat('-', 100));
        $this->info("DRY-RUN: {$valid} valid, {$warnings} warning, {$failed} error. TIDAK ADA data yang ditulis.");
        if (count($data['rows']) > 50) {
            $this->line('(hanya 50 baris pertama ditampilkan)');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
