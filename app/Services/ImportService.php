<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\ImportRowFingerprint;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\LetterRegister;
use App\Models\LetterType;
use App\Models\Receipt;
use App\Models\StockLedger;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\SpreadsheetReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Legacy spreadsheet import: UPLOAD → SHEET → MAP → VALIDATE → PREVIEW → IMPORT.
 * Native .xlsx / .xls / .csv support (macro-enabled rejected).
 * Idempotent via natural keys (code/number) + persisted row fingerprints
 * + file SHA-256 warning. Legacy rows never auto-post journals unless the
 * batch mode explicitly asks for opening entries (STOCK_AND_ACCOUNTING).
 */
final class ImportService
{
    /** import modes per type (PART 35): default keeps legacy data out of the books */
    public const MODES = [
        'legacy_invoice' => ['REGISTER_ONLY', 'OPENING_AR'],
        'legacy_receipt' => ['HISTORY_ONLY', 'OPENING_PAYMENT'],
        'opening_stock' => ['STOCK_ONLY', 'STOCK_AND_ACCOUNTING'],
    ];

    public const DEFAULT_MODES = [
        'legacy_invoice' => 'REGISTER_ONLY',
        'legacy_receipt' => 'HISTORY_ONLY',
        'opening_stock' => 'STOCK_ONLY',
    ];

    /**
     * @return array<string, array{label:string, fields:array<string,string>, required:string[]}>
     */
    public static function types(): array
    {
        return [
            'sparepart_master' => ['label' => 'Master Sparepart', 'fields' => [
                'code' => 'KODE', 'name' => 'NAMA SPAREPART', 'category' => 'KATEGORI',
                'unit' => 'SATUAN', 'location' => 'LOKASI', 'brand' => 'BRAND',
                'part_number' => 'PART NUMBER', 'min_stock' => 'MINIMUM STOCK',
                'max_stock' => 'MAXIMUM STOCK', 'reorder_point' => 'REORDER POINT',
                'supplier' => 'SUPPLIER', 'purchase_price' => 'PURCHASE PRICE',
            ], 'required' => ['code', 'name']],
            'opening_stock' => ['label' => 'Opening Stock / Stok Lama', 'fields' => [
                'date' => 'TANGGAL', 'code' => 'KODE SPAREPART', 'warehouse' => 'WAREHOUSE',
                'location' => 'RAK/BIN', 'qty' => 'QTY', 'cost' => 'COST',
                'reference' => 'KETERANGAN', 'notes' => 'CATATAN',
            ], 'required' => ['code', 'warehouse', 'qty']],
            'letter_register' => ['label' => 'Register Surat', 'fields' => [
                'number' => 'NOMOR SURAT', 'date' => 'TANGGAL', 'type' => 'JENIS SURAT',
                'subject' => 'PERIHAL', 'recipient' => 'TUJUAN', 'status' => 'STATUS',
                'notes' => 'KETERANGAN',
            ], 'required' => ['number', 'subject']],
            'legacy_invoice' => ['label' => 'Register Invoice Lama', 'fields' => [
                'number' => 'NOMOR INVOICE', 'date' => 'TANGGAL', 'customer' => 'CUSTOMER',
                'total' => 'TOTAL', 'status' => 'STATUS', 'notes' => 'KETERANGAN',
            ], 'required' => ['number', 'total']],
            'legacy_receipt' => ['label' => 'Register Kwitansi Lama', 'fields' => [
                'number' => 'NOMOR KWITANSI', 'date' => 'TANGGAL', 'payer' => 'CUSTOMER / PAYER',
                'amount' => 'AMOUNT', 'method' => 'PAYMENT METHOD', 'status' => 'STATUS',
            ], 'required' => ['number', 'amount']],
        ];
    }

    /**
     * Alias map used for auto-mapping (PART 32). Normalization collapses
     * case/space/underscore/dash so "No. Surat", "NOMOR_SURAT", "nomor-surat"
     * all resolve to the same key.
     */
    public static function aliases(): array
    {
        return [
            'NOMOR SURAT' => 'number', 'NO SURAT' => 'number',             'NOMOR' => 'number', 'NO' => 'number',
            'NOMOR INVOICE' => 'number', 'NO INVOICE' => 'number',
            'NOMOR KWITANSI' => 'number', 'NO KWITANSI' => 'number',
            'KODE' => 'code', 'KODE BARANG' => 'code', 'KODE SPAREPART' => 'code',
            'NAMA' => 'name', 'NAMA BARANG' => 'name', 'NAMA SPAREPART' => 'name',
            'QTY' => 'qty', 'JUMLAH' => 'qty', 'QTY MASUK' => 'qty', 'QUANTITY' => 'qty',
            'STOK' => 'qty', 'STOCK' => 'qty', 'SALDO' => 'qty', 'SALDO AKHIR' => 'qty',
            'TANGGAL' => 'date', 'TGL' => 'date',
            'TOTAL' => 'total', 'NILAI' => 'total', 'AMOUNT' => 'amount',
            'CUSTOMER' => 'customer', 'PELANGGAN' => 'customer', 'PAYER' => 'payer',
            'CUSTOMER / PAYER' => 'payer',
            'WAREHOUSE' => 'warehouse', 'GUDANG' => 'warehouse',
            'LOKASI' => 'location', 'RAK/BIN' => 'location', 'RAK' => 'location', 'BIN' => 'location',
            'KATEGORI' => 'category', 'SATUAN' => 'unit', 'BRAND' => 'brand', 'MERK' => 'brand',
            'PART NUMBER' => 'part_number', 'PART NO' => 'part_number',
            'MINIMUM STOCK' => 'min_stock', 'MIN STOCK' => 'min_stock', 'MIN STOK' => 'min_stock',
            'MAXIMUM STOCK' => 'max_stock', 'MAX STOCK' => 'max_stock', 'MAX STOK' => 'max_stock',
            'REORDER POINT' => 'reorder_point', 'ROP' => 'reorder_point',
            'SUPPLIER' => 'supplier', 'VENDOR' => 'supplier', 'PEMASOK' => 'supplier',
            'PURCHASE PRICE' => 'purchase_price', 'HARGA BELI' => 'purchase_price',
            'COST' => 'cost', 'HARGA' => 'cost', 'HPP' => 'cost',
            'JENIS SURAT' => 'type', 'PERIHAL' => 'subject', 'SUBJEK' => 'subject',
            'TUJUAN' => 'recipient', 'STATUS' => 'status', 'KETERANGAN' => 'notes',
            'CATATAN' => 'notes', 'PAYMENT METHOD' => 'method', 'METODE' => 'method',
            'REFERENSI' => 'reference', 'REFERENCE' => 'reference',
        ];
    }

    /**
     * Normalize a header cell for matching: uppercase, collapse separators.
     */
    public static function normalizeHeader(string $header): string
    {
        $h = mb_strtoupper(trim($header));
        $h = preg_replace('/[._\-\/]+/', ' ', $h);
        $h = preg_replace('/\s+/', ' ', $h);
        $h = trim($h);

        // strip trailing dots that survived (e.g. "NO." already became "NO")
        return $h;
    }

    /**
     * Auto-map spreadsheet headers to import fields using the alias table.
     * Returns [csvHeader => field]. Unrecognized headers map to null.
     */
    public static function autoMap(array $headers, string $type): array
    {
        $aliases = self::aliases();
        $fields = self::types()[$type]['fields'];
        $fieldKeys = array_keys($fields);
        $map = [];
        foreach ($headers as $header) {
            $normalized = self::normalizeHeader((string) $header);
            $field = null;
            if (isset($aliases[$normalized])) {
                $candidate = $aliases[$normalized];
                if (in_array($candidate, $fieldKeys, true)) {
                    $field = $candidate;
                }
            }
            if ($field === null) {
                // direct match against field label
                foreach ($fields as $key => $label) {
                    if (self::normalizeHeader($label) === $normalized) {
                        $field = $key;
                        break;
                    }
                }
            }
            if ($field === null) {
                // direct match against field key
                foreach ($fieldKeys as $key) {
                    if (self::normalizeHeader($key) === $normalized) {
                        $field = $key;
                        break;
                    }
                }
            }
            $map[$header] = $field;
        }
        // first "number"-ish column wins for number; drop later duplicates
        $taken = [];
        foreach ($map as $header => $field) {
            if ($field !== null) {
                if (in_array($field, $taken, true)) {
                    $map[$header] = null;
                } else {
                    $taken[] = $field;
                }
            }
        }

        return $map;
    }

    /**
     * Read the workbook (csv/xlsx/xls) of a batch, optionally a named sheet.
     */
    public static function readWorkbook(ImportBatch $batch, int $maxRows = 5000): array
    {
        $path = Storage::path($batch->file_name);

        return SpreadsheetReader::read($path, $batch->sheet, $maxRows);
    }

    /**
     * Back-compat: existing tests/readers call readCsv.
     */
    public static function readCsv(ImportBatch $batch, int $maxRows = 5000): array
    {
        return self::readWorkbook($batch, $maxRows);
    }

    public static function fileHash(ImportBatch $batch): string
    {
        return hash_file('sha256', Storage::path($batch->file_name)) ?: '';
    }

    /**
     * Has this exact file content been imported before (any batch)?
     */
    public static function fileSeenBefore(string $hash): bool
    {
        if ($hash === '') {
            return false;
        }

        return ImportBatch::where('file_hash', $hash)->where('status', 'IMPORTED')->exists();
    }

    /**
     * @return array{headers:array, rows:array, total:int, valid:int, warnings:int, failed:int, errors:array}
     */
    public static function validate(ImportBatch $batch, array $columnMap): array
    {
        $def = self::types()[$batch->type];
        $data = self::readWorkbook($batch);
        $result = ['headers' => $data['headers'], 'rows' => [], 'total' => 0, 'valid' => 0, 'warnings' => 0, 'failed' => 0, 'errors' => [], 'duplicates' => 0];
        $hash = self::fileHash($batch);
        $seenBefore = self::fileSeenBefore($hash);
        if ($seenBefore) {
            $result['errors'][] = ['row' => 0, 'error' => 'PERINGATAN: file dengan konten identik (SHA-256 sama) pernah diimport sebelumnya. Gunakan Force Import jika memang sengaja.'];
        }
        foreach ($data['rows'] as $i => $csvRow) {
            $rowNum = $i + 2;
            $mapped = [];
            foreach ($columnMap as $csvHeader => $field) {
                if ($field && array_key_exists($csvHeader, $csvRow)) {
                    $mapped[$field] = trim((string) $csvRow[$csvHeader]);
                }
            }
            $errors = [];
            foreach ($def['required'] as $req) {
                if (($mapped[$req] ?? '') === '') {
                    $errors[] = "Kolom {$req} wajib diisi";
                }
            }
            $custom = self::validateRow($batch->type, $mapped, $rowNum);
            $errors = array_merge($errors, $custom['errors']);
            $result['total']++;
            $mapped['_row'] = $rowNum;
            $fingerprint = self::fingerprint($batch, $mapped);
            $mapped['_fingerprint'] = $fingerprint;
            $duplicate = $fingerprint !== '' && ImportRowFingerprint::where('type', $batch->type)->where('fingerprint', $fingerprint)->exists();
            $mapped['_duplicate'] = $duplicate;
            if ($errors !== []) {
                $result['failed']++;
                foreach ($errors as $e) {
                    $result['errors'][] = ['row' => $rowNum, 'error' => $e];
                }
            } elseif ($duplicate) {
                $result['duplicates']++;
            } elseif ($custom['warning']) {
                $result['warnings']++;
            } else {
                $result['valid']++;
            }
            if (count($result['rows']) < 20) {
                $result['rows'][] = $mapped + ['_errors' => $errors];
            }
        }

        return $result;
    }

    /**
     * Stable row fingerprint from the fields that matter (PART 37):
     * document number/date/item/qty/amount/reference + batch scope.
     */
    public static function fingerprint(ImportBatch $batch, array $mapped): string
    {
        $keyFields = match ($batch->type) {
            'sparepart_master' => ['code', 'name'],
            'opening_stock' => ['code', 'warehouse', 'qty', 'cost', 'date'],
            'letter_register' => ['number', 'date', 'subject'],
            'legacy_invoice' => ['number', 'date', 'total'],
            'legacy_receipt' => ['number', 'date', 'amount'],
            default => [],
        };
        if ($keyFields === []) {
            return '';
        }
        $parts = [$batch->type, $batch->company_id, $batch->site_id, $batch->warehouse_id];
        foreach ($keyFields as $field) {
            $parts[] = $mapped[$field] ?? '';
        }

        return SparepartService::fingerprint($parts);
    }

    /**
     * @return array{errors:string[], warning:bool}
     */
    protected static function validateRow(string $type, array $row, int $rowNum): array
    {
        $errors = [];
        $warning = false;
        foreach (['qty', 'cost', 'total', 'amount', 'min_stock', 'max_stock', 'reorder_point', 'purchase_price'] as $num) {
            if (isset($row[$num]) && $row[$num] !== '' && self::parseNumber($row[$num]) === null) {
                $errors[] = "Kolom {$num} bukan angka valid (diterima: '{$row[$num]}')";
            }
        }
        if (isset($row['date']) && $row['date'] !== '') {
            $parsed = self::parseDateWithWarning($row['date']);
            if ($parsed === null) {
                $errors[] = "Tanggal '{$row['date']}' tidak dapat dibaca (format dikenal: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, serial Excel)";
            } elseif ($parsed['ambiguous']) {
                $warning = true;
            }
        }
        if (in_array($type, ['sparepart_master', 'opening_stock'], true) && ($row['code'] ?? '') !== '') {
            $exists = Item::where('code', $row['code'])->exists();
            if ($type === 'sparepart_master' && $exists) {
                $warning = true;
            }
        }
        if ($type === 'opening_stock') {
            if (($row['warehouse'] ?? '') !== '' && ! Warehouse::where('code', $row['warehouse'])->exists()) {
                $errors[] = "Warehouse '{$row['warehouse']}' tidak ditemukan";
            }
            if (($row['code'] ?? '') !== '' && ! Item::where('code', $row['code'])->exists()) {
                $errors[] = "Sparepart '{$row['code']}' tidak ditemukan di master";
            }
        }
        if ($type === 'legacy_invoice') {
            if (($row['customer'] ?? '') !== '' && ! Customer::where('name', 'like', '%'.$row['customer'].'%')->exists()) {
                $errors[] = "Customer '{$row['customer']}' tidak ditemukan di master";
            }
            if (strlen($row['number'] ?? '') > 50) {
                $errors[] = 'Nomor invoice maksimal 50 karakter';
            }
        }
        if (in_array($type, ['letter_register', 'legacy_invoice', 'legacy_receipt'], true) && ($row['number'] ?? '') !== '') {
            $exists = match ($type) {
                'letter_register' => LetterRegister::where('number', $row['number'])->exists(),
                'legacy_invoice' => Invoice::where('number', $row['number'])->exists(),
                default => Receipt::where('number', $row['number'])->exists(),
            };
            if ($exists) {
                $warning = true;
            }
        }

        return ['errors' => $errors, 'warning' => $warning];
    }

    /**
     * Execute a validated batch. Returns [imported, skipped].
     * Mode REGISTER_ONLY/HISTORY_ONLY/STOCK_ONLY = no accounting side effects.
     */
    public static function execute(ImportBatch $batch, bool $dryRun = false): array
    {
        $data = self::readWorkbook($batch);
        $map = $batch->column_map ?? [];
        $imported = 0;
        $skipped = 0;
        $fingerprints = [];
        $import = function () use ($batch, $data, $map, $dryRun, &$imported, &$skipped, &$fingerprints) {
            foreach ($data['rows'] as $i => $csvRow) {
                $rowNum = $i + 2;
                $mapped = [];
                foreach ($map as $csvHeader => $field) {
                    if ($field && array_key_exists($csvHeader, $csvRow)) {
                        $mapped[$field] = trim((string) $csvRow[$csvHeader]);
                    }
                }
                $fingerprint = self::fingerprint($batch, $mapped);
                if ($fingerprint !== '') {
                    // in-batch duplicate + cross-batch duplicate
                    if (in_array($fingerprint, $fingerprints, true)
                        || (! $dryRun && ImportRowFingerprint::where('type', $batch->type)->where('fingerprint', $fingerprint)->exists())) {
                        $skipped++;

                        continue;
                    }
                    $fingerprints[$rowNum] = $fingerprint;
                }
                // dry-run NEVER writes: rows are counted only
                if ($dryRun) {
                    $imported++;

                    continue;
                }
                if (self::insertRow($batch, $mapped)) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }
            if (! $dryRun) {
                foreach ($fingerprints as $rowNum => $fingerprint) {
                    ImportRowFingerprint::create([
                        'import_batch_id' => $batch->id,
                        'type' => $batch->type,
                        'fingerprint' => $fingerprint,
                        'row_number' => $rowNum,
                    ]);
                }
                $batch->update([
                    'status' => 'IMPORTED', 'imported_rows' => $imported, 'skipped_rows' => $skipped,
                    'imported_by' => auth()->id(), 'imported_at' => now(),
                ]);
            }
        };

        if ($dryRun) {
            $import();
        } else {
            DB::transaction($import);
            AuditService::log('CREATE', 'IMPORT', $batch->id, ImportBatch::class, null, [
                'type' => $batch->type, 'imported' => $imported, 'skipped' => $skipped,
                'mode' => $batch->mode ?? 'DEFAULT', 'sheet' => $batch->sheet,
            ]);
        }

        return [$imported, $skipped];
    }

    /**
     * Locale-aware number parser for Indonesian legacy formats (PART 34):
     * "Rp 12.000.000" → 12000000; "12.000.000" → 12000000; "12,000,000" → 12000000
     * "2.000" → 2000 (dot thousands, no decimal); "2,5" → 2.5; "2.5" → 2.5.
     */
    public static function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $s = trim((string) $value);
        // currency prefixes/suffixes
        $s = preg_replace('/^(rp|idr)\s*/i', '', $s);
        $s = preg_replace('/\s*(idr|-rp|\-idr)$/i', '', $s);
        $s = str_replace([' ', "\xc2\xa0"], '', $s);
        if ($s === '') {
            return 0.0;
        }
        $negative = str_starts_with($s, '(') && str_ends_with($s, ')');
        $s = trim($s, '()-');
        if (! preg_match('/^[\d.,]+$/', $s)) {
            return null;
        }
        $hasComma = str_contains($s, ',');
        $hasDot = str_contains($s, '.');
        if ($hasComma && $hasDot) {
            // last separator wins as the decimal separator
            if (strrpos($s, ',') > strrpos($s, '.')) {
                // 1.234.567,89
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // 1,234,567.89
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            $parts = explode(',', $s);
            $last = end($parts);
            if (count($parts) > 1 && strlen($last) === 3) {
                // 1,234,567 thousands grouping OR 700,000 — both parse as plain int
                $s = str_replace(',', '', $s);
            } else {
                // 2,5 decimal comma
                $s = str_replace(',', '.', $s);
            }
        } elseif ($hasDot) {
            $parts = explode('.', $s);
            $last = end($parts);
            if (count($parts) > 1 && strlen($last) !== 3) {
                // 2.5 decimal dot
            } elseif (count($parts) > 1) {
                // 2.000 or 1.234.567 thousands grouping
                $s = str_replace('.', '', $s);
            }
        }
        if (! is_numeric($s)) {
            return null;
        }
        $num = (float) $s;

        return $negative ? -$num : $num;
    }

    /**
     * Date normalization (PART 33): ISO, dd/mm/yyyy, dd-mm-yyyy, Excel serial.
     * Ambiguous dd/mm vs mm/dd never silently reinterpreted — dd/mm wins
     * (Indonesian locale) and the caller marks the row WARNING.
     */
    public static function parseDateWithWarning(?string $value): ?array
    {
        if (! $value) {
            return ['date' => now()->toDateString(), 'ambiguous' => false];
        }
        $v = trim((string) $value);
        // Excel serial (5 digits or plain number 30000-60000)
        if (preg_match('/^\d{5}$/', $v) || (preg_match('/^\d+$/', $v) && (int) $v >= 30000 && (int) $v <= 60000)) {
            return ['date' => SpreadsheetReader::excelSerialToDate((float) $v)->toDateString(), 'ambiguous' => false];
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd/m/y', 'd-m-y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $v);
                if ($date === false) {
                    continue;
                }
                $ambiguous = false;
                // dd/mm vs mm/dd: only flag when day/month order is uncertain
                if (in_array($format, ['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y'], true)) {
                    $seps = preg_split('/[\/\-]/', $v);
                    if (count($seps) === 3 && (int) $seps[0] >= 1 && (int) $seps[0] <= 12 && (int) $seps[1] >= 1 && (int) $seps[1] <= 12 && $seps[0] !== $seps[1]) {
                        // both dd/mm and mm/dd interpretations are plausible
                        $ambiguous = true;
                    }
                }
                if (! checkdate((int) $date->format('m'), (int) $date->format('d'), (int) $date->format('Y'))) {
                    continue;
                }

                return ['date' => $date->toDateString(), 'ambiguous' => $ambiguous];
            } catch (\Throwable) {
            }
        }

        return null;
    }

    protected static function parseDate(?string $value): string
    {
        return self::parseDateWithWarning($value)['date'] ?? now()->toDateString();
    }

    /**
     * Insert one mapped row. Returns false when skipped as duplicate.
     */
    protected static function insertRow(ImportBatch $batch, array $row): bool
    {
        return match ($batch->type) {
            'sparepart_master' => self::insertSparepart($batch, $row),
            'opening_stock' => self::insertOpening($batch, $row),
            'letter_register' => self::insertLetter($batch, $row),
            'legacy_invoice' => self::insertLegacyInvoice($batch, $row),
            'legacy_receipt' => self::insertLegacyReceipt($batch, $row),
            default => false,
        };
    }

    protected static function insertSparepart(ImportBatch $batch, array $row): bool
    {
        if (($row['code'] ?? '') === '' || Item::where('code', $row['code'])->exists()) {
            return false;
        }
        $category = ItemCategory::firstOrCreate(['code' => strtoupper(substr($row['category'] ?? 'SPAREPART', 0, 20))], ['name' => $row['category'] ?? 'Sparepart', 'type' => 'SPAREPART']);
        $unit = Unit::firstOrCreate(['code' => strtoupper(substr($row['unit'] ?? 'PCS', 0, 10))], ['name' => $row['unit'] ?? 'Pcs']);
        $location = null;
        if (($row['location'] ?? '') !== '') {
            $location = StorageLocation::where('code', $row['location'])->first();
        }
        $supplier = null;
        if (($row['supplier'] ?? '') !== '') {
            $supplier = Supplier::where('name', 'like', '%'.$row['supplier'].'%')->first();
        }
        Item::create([
            'code' => $row['code'], 'name' => $row['name'] ?? $row['code'],
            'item_category_id' => $category->id, 'type' => 'SPAREPART', 'unit_id' => $unit->id,
            'brand' => $row['brand'] ?? null, 'part_number' => $row['part_number'] ?? null,
            'storage_location_id' => $location?->id,
            'min_stock' => self::parseNumber($row['min_stock'] ?? 0) ?? 0, 'max_stock' => self::parseNumber($row['max_stock'] ?? 0) ?: null,
            'reorder_point' => self::parseNumber($row['reorder_point'] ?? 0) ?? 0,
            'preferred_supplier_id' => $supplier?->id,
            'standard_cost' => self::parseNumber($row['purchase_price'] ?? 0) ?? 0,
            'last_purchase_price' => self::parseNumber($row['purchase_price'] ?? 0) ?? 0,
            'status' => true, 'source' => 'LEGACY_IMPORT',
            'legacy_reference' => $batch->file_name, 'created_by' => auth()->id(),
        ]);

        return true;
    }

    protected static function insertOpening(ImportBatch $batch, array $row): bool
    {
        $item = Item::where('code', $row['code'] ?? '')->first();
        $wh = $batch->warehouse_id ? Warehouse::find($batch->warehouse_id) : Warehouse::where('code', $row['warehouse'] ?? '')->first();
        if (! $item || ! $wh) {
            return false;
        }
        $qty = self::parseNumber($row['qty'] ?? 0) ?? 0;
        if ($qty <= 0) {
            return false;
        }
        $duplicate = StockLedger::where('warehouse_id', $wh->id)->where('item_id', $item->id)
            ->where('movement_type', 'OPENING')->where('import_batch_id', 'LEGACY-'.$batch->id)->exists();
        if ($duplicate) {
            return false;
        }
        $location = null;
        if (($row['location'] ?? '') !== '') {
            $location = StorageLocation::where('code', $row['location'])->where('warehouse_id', $wh->id)->first();
        }
        $ledger = StockService::move($wh->id, $item->id, 'OPENING', $qty, 0, $wh->company_id, $wh->site_id, $batch->id, 'OPENING_BALANCE', $row['reference'] ?? ('STOK-LAMA-'.$batch->id), self::parseNumber($row['cost'] ?? 0) ?: null, self::parseDate($row['date'] ?? null));
        $ledger->update([
            'source' => 'LEGACY_IMPORT', 'import_batch_id' => 'LEGACY-'.$batch->id,
            'storage_location_id' => $location?->id, 'notes' => $row['notes'] ?? 'STOK LAMA',
        ]);
        // STOCK_AND_ACCOUNTING mode additionally posts the opening balance journal
        if (($batch->mode ?? self::DEFAULT_MODES['opening_stock']) === 'STOCK_AND_ACCOUNTING') {
            self::postOpeningStockJournal($batch, $item, $qty, self::parseNumber($row['cost'] ?? 0) ?: 0, $ledger);
        }

        return true;
    }

    /**
     * Optional opening-balance journal (only in STOCK_AND_ACCOUNTING mode):
     * Dr Inventory / Cr Opening Balance Equity.
     */
    protected static function postOpeningStockJournal(ImportBatch $batch, Item $item, float $qty, float $unitCost, StockLedger $ledger): void
    {
        $value = round($qty * $unitCost, 2);
        if ($value <= 0) {
            return;
        }
        $invMap = match ($item->type) {
            'PRODUCT' => 'INVENTORY_FG',
            'RAW' => 'INVENTORY_RAW',
            'SPAREPART' => 'INVENTORY_SPAREPART',
            default => 'INVENTORY_GENERAL',
        };
        $journal = AccountingService::post($batch->company_id ?? $ledger->company_id, $ledger->trx_date, [
            ['code' => AccountingService::map($invMap), 'debit' => $value, 'memo' => 'Opening stock '.$item->code],
            ['code' => AccountingService::map('OPENING_BALANCE_EQUITY'), 'credit' => $value, 'memo' => 'Saldo awal stok '.$item->code],
        ], 'OPENING_STOCK', $batch->id, 'IMP-'.$batch->id, 'Opening balance dari import', 'IMP');
        $ledger->update(['notes' => ($ledger->notes ? $ledger->notes.' ' : '').'[JV:'.$journal->number.']']);
    }

    protected static function insertLetter(ImportBatch $batch, array $row): bool
    {
        if (($row['number'] ?? '') === '' || LetterRegister::where('number', $row['number'])->exists()) {
            return false;
        }
        $type = LetterType::where('name', 'like', '%'.($row['type'] ?? '').'%')->orWhere('code', $row['type'] ?? '')->first()
            ?? LetterType::where('code', 'EXT')->first();
        $company = $batch->company_id ? Company::find($batch->company_id) : Company::first();
        LetterRegister::create([
            'number' => $row['number'], 'letter_type_id' => $type?->id,
            'letter_date' => self::parseDate($row['date'] ?? null),
            'subject' => $row['subject'] ?? '-', 'recipient_type' => 'OTHER',
            'recipient_name' => $row['recipient'] ?? null,
            'notes' => $row['notes'] ?? null,
            'status' => in_array(strtoupper($row['status'] ?? ''), LetterRegister::STATUSES, true) ? strtoupper($row['status']) : 'ARCHIVED',
            'company_id' => $company?->id, 'source' => 'LEGACY_IMPORT',
            'legacy_reference' => $batch->file_name, 'created_by' => auth()->id(),
        ]);

        return true;
    }

    protected static function insertLegacyInvoice(ImportBatch $batch, array $row): bool
    {
        if (($row['number'] ?? '') === '' || Invoice::where('number', $row['number'])->exists()) {
            return false;
        }
        $company = $batch->company_id ? Company::find($batch->company_id) : Company::first();
        $customer = null;
        if (($row['customer'] ?? '') !== '') {
            $customer = Customer::where('name', 'like', '%'.$row['customer'].'%')->first();
        }
        if (! $customer) {
            return false;
        }
        // REGISTER_ONLY (default): record only, never auto-journal historic amounts.
        $invoice = Invoice::create([
            'number' => $row['number'], 'company_id' => $company?->id,
            'customer_id' => $customer?->id, 'invoice_date' => self::parseDate($row['date'] ?? null),
            'subtotal' => self::parseNumber($row['total'] ?? 0) ?? 0, 'tax_amount' => 0,
            'total' => self::parseNumber($row['total'] ?? 0) ?? 0, 'paid_amount' => 0,
            'status' => in_array(strtoupper($row['status'] ?? ''), ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED', 'VOID'], true) ? strtoupper($row['status']) : 'POSTED',
            'source' => 'LEGACY_IMPORT', 'legacy_reference' => $batch->file_name,
            'created_by' => auth()->id(),
        ]);
        // OPENING_AR mode: Dr AR / Cr Opening Balance Equity for the outstanding part
        if (($batch->mode ?? self::DEFAULT_MODES['legacy_invoice']) === 'OPENING_AR' && in_array($invoice->status, ['POSTED', 'PARTIALLY_PAID', 'PAID'], true)) {
            $outstanding = (float) $invoice->total - (float) $invoice->paid_amount;
            if ($outstanding > 0) {
                AccountingService::post($invoice->company_id, $invoice->invoice_date->toDateString(), [
                    ['code' => AccountingService::map('AR_TRADE'), 'debit' => $outstanding, 'memo' => 'Opening AR '.$invoice->number],
                    ['code' => AccountingService::map('OPENING_BALANCE_EQUITY'), 'credit' => $outstanding, 'memo' => 'Saldo awal piutang '.$invoice->number],
                ], 'OPENING_AR', $batch->id, $invoice->number, 'Opening AR dari import', 'IMP');
            }
        }

        return true;
    }

    protected static function insertLegacyReceipt(ImportBatch $batch, array $row): bool
    {
        if (($row['number'] ?? '') === '' || Receipt::where('number', $row['number'])->exists()) {
            return false;
        }
        // HISTORY_ONLY (default): legacy receipts without a payment link are
        // recorded as documents only — never create money.
        $company = $batch->company_id ? Company::find($batch->company_id) : Company::first();
        $receipt = Receipt::create([
            'number' => $row['number'], 'receipt_date' => self::parseDate($row['date'] ?? null),
            'company_id' => $company?->id, 'payment_id' => null,
            'payer_name' => $row['payer'] ?? '-', 'description' => 'Legacy import',
            'amount' => self::parseNumber($row['amount'] ?? 0) ?? 0,
            'payment_method' => in_array(strtoupper($row['method'] ?? ''), ['CASH', 'TRANSFER', 'GIRO', 'DEPOSIT'], true) ? strtoupper($row['method']) : 'TRANSFER',
            'status' => in_array(strtoupper($row['status'] ?? ''), Receipt::STATUSES, true) ? strtoupper($row['status']) : 'CONFIRMED',
            'source' => 'LEGACY_IMPORT', 'legacy_reference' => $batch->file_name,
            'created_by' => auth()->id(),
        ]);
        // OPENING_PAYMENT mode: Dr Bank/Cash / Cr Opening Balance Equity
        if (($batch->mode ?? self::DEFAULT_MODES['legacy_receipt']) === 'OPENING_PAYMENT' && (float) $receipt->amount > 0) {
            AccountingService::post($receipt->company_id, $receipt->receipt_date, [
                ['code' => AccountingService::map('CASH_MAIN'), 'debit' => (float) $receipt->amount, 'memo' => 'Opening payment '.$receipt->number],
                ['code' => AccountingService::map('OPENING_BALANCE_EQUITY'), 'credit' => (float) $receipt->amount, 'memo' => 'Saldo awal penerimaan '.$receipt->number],
            ], 'OPENING_PAYMENT', $batch->id, $receipt->number, 'Opening payment dari import', 'IMP');
        }

        return true;
    }
}
