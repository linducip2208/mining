<?php

namespace App\Services\Bfj;

use App\Models\BfjImportProfile;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\LegacyImportBatch;
use App\Models\LegacyImportIssue;
use App\Models\LegacyImportLink;
use App\Models\LegacyImportMatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyImportSheet;
use App\Models\LetterRegister;
use App\Models\LetterType;
use App\Models\Receipt;
use App\Models\StockLedger;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Support\SpreadsheetReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * BFJ legacy import orchestrator (§2, §69-§73, §84-§93, §110-§112, §116-§118).
 *
 * Pipeline: UPLOAD → SCAN → MAP → VALIDATE → DRY RUN → IMPORT → RECONCILE.
 * Nothing touches transactional tables before explicit import() with a mode.
 * Default modes are history/reconciliation-only (no stock/accounting effect).
 */
final class BfjImportEngine
{
    public const LIFECYCLE = ['UPLOADED', 'SCANNED', 'MAPPED', 'VALIDATED', 'READY', 'IMPORTING', 'IMPORTED', 'RECONCILING', 'RECONCILED', 'PARTIAL', 'FAILED', 'ROLLED_BACK'];

    /** Classic small-table types that keep the legacy assoc pipeline (header row 1, no merges). */
    public const SIMPLE_TYPES = [
        'SALES', 'CUSTOMER_DEPOSIT', 'FINANCE', 'INVOICE_REGISTER', 'RECEIPT_REGISTER',
        'CORRESPONDENCE', 'SPAREPART_MASTER', 'SPAREPART_OPENING', 'SPAREPART_ISSUE',
        'STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT',
    ];

    public const SUMMARY_TYPES = [
        'SALES_RECAP_MATRIX', 'SALES_RETAIL_MATRIX', 'DEPOSIT_SISA', 'DEPOSIT_RETAIL_CASH',
        'FINANCE_STATEMENT', 'PAYROLL_RECAP', 'PAYROLL_SECURITY',
        'STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT',
    ];

    /** Scan workbook into staging (chunked, no ERP writes). */
    public static function scan(string $storedPath, array $opts = []): LegacyImportBatch
    {
        $profile = BfjImportProfile::bfj();
        $abs = Storage::disk('local')->path($storedPath);
        $sheets = SpreadsheetReader::sheets($abs);
        $fileInfo = self::classifyFile($storedPath, $sheets);

        $batch = LegacyImportBatch::create([
            'profile_id' => $profile->id,
            'file_name' => $storedPath,
            'file_hash' => BfjFingerprinter::file($abs),
            'file_ext' => strtolower(pathinfo($storedPath, PATHINFO_EXTENSION)),
            'file_size' => @filesize($abs) ?: 0,
            'mode' => $opts['mode'] ?? $profile->cfg('sales_default_mode', 'HISTORY_ONLY'),
            'status' => 'SCANNED',
            'company_id' => $opts['company_id'] ?? null,
            'site_id' => $opts['site_id'] ?? null,
            'warehouse_id' => $opts['warehouse_id'] ?? null,
            'imported_by' => $opts['user_id'] ?? null,
            'cutoff_date' => $opts['cutoff_date'] ?? null,
            'cutoffs' => $opts['cutoffs'] ?? null,
        ]);

        if (LegacyImportBatch::where('file_hash', $batch->file_hash)->where('id', '<', $batch->id)->exists()) {
            self::issue($batch, null, null, 'DUPLICATE_REFERENCE', 'WARNING', 'File hash already imported — re-import will be idempotent');
        }

        foreach ($sheets as $sheetName) {
            try {
                $rich = SpreadsheetReader::readRich($abs, $sheetName, 5000);
            } catch (\Throwable $e) {
                self::issue($batch, null, null, 'FORMULA_ERROR', 'ERROR', "Sheet {$sheetName}: ".$e->getMessage());

                continue;
            }
            $grid = BfjSheetLayout::forwardFill($rich['grid'], $rich['merges']);
            $layout = BfjSheetLayout::analyze($grid, $rich['merges'], $sheetName);
            $cls = self::resolveSheetType($sheetName, $layout, $fileInfo, $opts['force_type'] ?? null);
            $sheet = LegacyImportSheet::create([
                'batch_id' => $batch->id,
                'sheet_name' => mb_substr($sheetName, 0, 120),
                'detected_type' => $cls['type'],
                'confidence' => $cls['confidence'],
                'row_count' => 0,
                'is_summary' => $cls['is_summary'],
                'action' => $cls['action'],
                'target_module' => $cls['type'],
                'layout' => ['header_row' => $layout['header_row'], 'span' => $layout['span'], 'score' => $layout['score'], 'domain' => $layout['domain'], 'title' => $layout['title']],
                'note' => $cls['note'] ?? null,
            ]);
            if ($cls['action'] === 'IGNORE') {
                self::issue($batch, $sheet->id, null, 'MISSING_FIELD', 'WARNING', "Sheet {$sheetName}: {$cls['type']} — {$cls['note']}");

                continue;
            }
            $simple = empty($rich['merges']) && ($layout['header_row'] === 1)
                && in_array($cls['type'], self::SIMPLE_TYPES, true)
                && ! in_array($cls['type'], ['STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT'], true);
            if ($simple) {
                $plain = SpreadsheetReader::read($abs, $sheetName, 5000);
                $headers = array_values(array_filter($plain['headers'] ?? [], fn ($h) => trim((string) $h) !== ''));
                self::stageRowsLegacy($batch, $sheet, $headers, $plain['rows'] ?? []);
            } else {
                $envelope = self::parseSheet($batch, $cls['type'], $layout, $grid, $sheetName);
                $sheet->update(['note' => trim(($sheet->note ? $sheet->note.' ' : '').json_encode($envelope['notes'] ?? []))]);
                self::stageEnvelope($batch, $sheet, $envelope);
            }
            $sheet->update(['row_count' => $sheet->rows()->count()]);
        }

        try {
            AuditService::log('CREATE', 'LEGACY_IMPORT', $batch->id, LegacyImportBatch::class, null, ['file' => $storedPath]);
        } catch (\Throwable) {
        }

        return $batch->load('sheets');
    }

    /** @param  array<int,array>  $rows */
    private static function stageRowsLegacy(LegacyImportBatch $batch, LegacyImportSheet $sheet, array $headers, array $rows): void
    {
        $tolerance = (float) ($batch->profile->cfg('amount_tolerance', 1) ?? 1);
        $seen = [];
        foreach (array_chunk($rows, 500) as $chunk) {
            $payload = [];
            foreach ($chunk as $idx => $cells) {
                $rowNum = $idx + 2; // header is row 1 (approx; chunk offset ignored for memory safety — fingerprint covers identity)
                // CSV reader returns assoc rows, XLSX/XLS return positional arrays — support both.
                $vals = is_array($cells) && ! array_is_list($cells) ? array_values($cells) : (array) $cells;
                $assoc = [];
                foreach ($headers as $i => $h) {
                    $assoc[trim((string) $h)] = $vals[$i] ?? null;
                }
                [$normalized, $issues, $fp] = self::normalizeRowLegacy($sheet->detected_type ?? 'SALES', $assoc, $tolerance);
                if (BfjRealCommon::any($issues, fn ($i) => ($i['code'] ?? '') === 'SKIP_ROW')) {
                    continue; // date-only skeleton rows carry no content
                }
                $status = 'READY';
                foreach ($issues as $iss) {
                    self::issue($batch, $sheet->id, $rowNum, $iss['code'], $iss['severity'], $iss['message']);
                    if ($iss['severity'] === 'ERROR') {
                        $status = 'ERROR';
                    } elseif ($status === 'READY') {
                        $status = 'WARNING';
                    }
                }
                if (isset($seen[$fp])) {
                    $status = 'DUPLICATE';
                    self::issue($batch, $sheet->id, $rowNum, 'DUPLICATE_REFERENCE', 'WARNING', 'Duplicate row fingerprint in source');
                }
                $seen[$fp] = true;
                if (LegacyImportRow::where('fingerprint', $fp)->whereHas('sheet', fn ($q) => $q->where('batch_id', $batch->id))->exists()) {
                    $status = 'DUPLICATE';
                }
                self::recordMatches($batch->id, $sheet->detected_type ?? '', $normalized);
                $payload[] = [
                    'sheet_id' => $sheet->id, 'row_number' => $rowNum,
                    'source' => json_encode($assoc, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                    'normalized' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                    'fingerprint' => $fp, 'status' => $status, 'posting_effect' => 'NONE',
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            LegacyImportRow::insert($payload);
        }
    }

    /** @return array{type:string,confidence:int,is_summary:bool,action:string,note:string|null} */
    private static function resolveSheetType(string $sheetName, array $layout, array $fileInfo, ?string $forceType): array
    {
        if ($forceType) {
            return [
                'type' => $forceType, 'confidence' => 100,
                'is_summary' => in_array($forceType, self::SUMMARY_TYPES, true),
                'action' => in_array($forceType, ['LOADING_LOG', 'CUSTOMER_STOCK', 'EMPTY', 'MANUAL_REVIEW'], true) ? 'IGNORE' : (in_array($forceType, self::SUMMARY_TYPES, true) ? 'RECONCILE_ONLY' : 'IMPORT'),
                'note' => 'operator-forced type',
            ];
        }
        $named = BfjClassifier::sheetNameType($sheetName);
        if ($named) {
            $type = $named['type'];
            $action = in_array($type, ['LOADING_LOG', 'CUSTOMER_STOCK', 'EMPTY'], true) ? 'IGNORE' : ($named['is_summary'] ? 'RECONCILE_ONLY' : 'IMPORT');
            $note = match ($type) {
                'LOADING_LOG' => 'loading-point log, no amounts — reference only',
                'CUSTOMER_STOCK' => 'customer stock log — reference only',
                'EMPTY' => 'empty sheet',
                default => null,
            };

            return ['type' => $type, 'confidence' => $named['confidence'], 'is_summary' => $named['is_summary'], 'action' => $action, 'note' => $note];
        }
        if ($layout['header_row'] === null) {
            if ($fileInfo['type'] === 'FINANCE') {
                return ['type' => 'FINANCE_STATEMENT', 'confidence' => 55, 'is_summary' => true, 'action' => 'RECONCILE_ONLY', 'note' => 'no table header — statement lines'];
            }

            return ['type' => 'MANUAL_REVIEW', 'confidence' => 20, 'is_summary' => false, 'action' => 'IGNORE', 'note' => 'no detectable table header'];
        }
        $headerNames = array_values($layout['headers']);
        $cls = BfjClassifier::classifySheet($sheetName, $headerNames);
        $type = self::refineByLayout($cls['type'], $layout, $fileInfo);
        $conf = $cls['confidence'];
        if ($conf < 50 && $fileInfo['confidence'] >= 70 && ! $cls['is_summary']) {
            $type = $fileInfo['type'];
            $conf = 55;
        }
        $isSummary = $cls['is_summary'] || in_array($type, self::SUMMARY_TYPES, true);

        return [
            'type' => $type, 'confidence' => $conf, 'is_summary' => $isSummary,
            'action' => $isSummary ? 'RECONCILE_ONLY' : 'IMPORT', 'note' => null,
        ];
    }

    private static function refineByLayout(string $type, array $layout, array $fileInfo): string
    {
        $domain = $layout['domain'] ?? '';
        $headers = array_values($layout['headers'] ?? []);
        $joined = ' | '.mb_strtoupper(implode(' | ', $headers));
        if ($domain === 'DOCUMENT') {
            if (str_contains($joined, 'NOMOR KWITANSI')) {
                return 'RECEIPT_REGISTER';
            }
            if (str_contains($joined, 'NOMOR INVOICE')) {
                return 'INVOICE_REGISTER';
            }
            if (str_contains($joined, 'NOMOR SURAT')) {
                return 'CORRESPONDENCE';
            }
        }
        if ($domain === 'SPAREPART') {
            if (str_contains($joined, 'STOK SISTEM') || str_contains($joined, 'STOK FISIK')) {
                return 'STOCK_OPNAME';
            }
            if (str_contains($joined, 'STOK AWAL')) {
                return 'STOCK_REPORT';
            }
            if (str_contains($joined, 'STOK MASUK') || str_contains($joined, 'SALDO STOK')) {
                return 'STOCK_CARD';
            }
            if (str_contains($joined, 'KELUAR')) {
                return 'SPAREPART_ISSUE';
            }
            if (str_contains($joined, 'MASUK')) {
                return 'SPAREPART_OPENING';
            }

            return 'SPAREPART_MASTER';
        }
        if ($domain === 'MATRIX') {
            return 'SALES_RECAP_MATRIX';
        }
        if ($domain === 'SISA') {
            return 'DEPOSIT_SISA';
        }
        if ($domain === 'RETAIL_CASH') {
            return 'DEPOSIT_RETAIL_CASH';
        }
        if ($domain === 'PAYROLL_RECAP') {
            return 'PAYROLL_RECAP';
        }
        if ($domain === 'SECURITY') {
            return 'PAYROLL_SECURITY';
        }
        if ($domain === 'PAYROLL' && $fileInfo['type'] === 'PAYROLL') {
            return 'PAYROLL_DAY';
        }
        if ($domain === 'FINANCE') {
            return str_contains($joined, 'TIPE') ? 'FINANCE_DETAIL' : 'FINANCE_STATEMENT';
        }
        if ($domain === 'DEPOSIT') {
            return 'CUSTOMER_DEPOSIT';
        }
        if ($domain === 'SALES') {
            return 'SALES';
        }

        return $type;
    }

    /**
     * Dispatch a layout-analyzed sheet to its real-file parser.
     *
     * @param  array{header_row:int,span:array{0:int,1:int},headers:array<int,string>,groups:array<int,string>,title:array<string,string|null>}  $layout
     * @param  array<int, array<int, array{v:string,f:string|null,cached:bool}>>  $grid
     */
    private static function parseSheet(LegacyImportBatch $batch, string $type, array $layout, array $grid, string $sheetName): array
    {
        $tolerance = (float) ($batch->profile->cfg('amount_tolerance', 1) ?? 1);
        $period = $layout['title']['period'] ?? null;
        switch ($type) {
            case 'SALES':
                return BfjRealSalesParser::daily($layout, $grid, $sheetName, $tolerance);
            case 'SALES_RECAP_MATRIX':
                return BfjRealSalesParser::recapMatrix($layout, $grid);
            case 'SALES_RETAIL_MATRIX':
                return BfjRealSalesParser::retailMatrix($layout, $grid);
            case 'CUSTOMER_DEPOSIT':
                $customer = match (mb_strtoupper($sheetName)) {
                    'RITEL TF' => 'RITEL TRANSFER',
                    'SMJ 2000M3', 'SMJ' => 'SMJ',
                    'BIMO' => 'PAK BIMO',
                    default => $sheetName,
                };
                $out = BfjRealDepositParser::customer($layout, $grid, $customer, $period);
                if (in_array(mb_strtoupper($sheetName), ['SMJ', 'SMJ 2000M3'], true)) {
                    $extra = BfjRealDepositParser::smjMonthly($layout, $grid);
                    $out['rows'] = array_merge($out['rows'], $extra['rows']);
                }

                return $out;
            case 'DEPOSIT_SISA':
                return BfjRealDepositParser::sisa($layout, $grid);
            case 'DEPOSIT_RETAIL_CASH':
                return BfjRealDepositParser::retailCash($layout, $grid);
            case 'FINANCE_DETAIL':
                $out = BfjRealFinanceParser::detail($layout, $grid, $period);
                $extra = BfjRealFinanceParser::summaryTable($grid, $layout['header_row']);
                $out['rows'] = array_merge($out['rows'], $extra['rows']);
                $company = mb_strtoupper($layout['title']['company'] ?? '');
                if (str_contains($company, 'LASEN') || str_contains($company, 'ALASEN')) {
                    $stmt = BfjRealFinanceParser::accountBlock($grid, $layout['header_row'], $sheetName);
                    $out['rows'] = array_merge($out['rows'], $stmt['rows']);
                }

                return $out;
            case 'FINANCE_STATEMENT':
                return BfjRealFinanceParser::statement($grid, $sheetName);
            case 'PAYROLL_DAY':
                [$emp, $role] = BfjRealPayrollParser::employeeFromTitle($grid, $sheetName);
                $days = BfjRealPayrollParser::employeeDays($layout, $grid, $emp, $role, $period);
                $recap = BfjRealPayrollParser::recapBlock($layout, $grid, $emp);
                $days['rows'] = array_merge($days['rows'], $recap['rows']);

                return $days;
            case 'PAYROLL_RECAP':
                return self::parsePayrollRecap($layout, $grid, $sheetName);
            case 'PAYROLL_SECURITY':
                return BfjRealPayrollParser::security($layout, $grid);
            case 'CORRESPONDENCE':
            case 'INVOICE_REGISTER':
            case 'RECEIPT_REGISTER':
            case 'SPAREPART_MASTER':
            case 'SPAREPART_OPENING':
            case 'SPAREPART_ISSUE':
            case 'STOCK_CARD':
            case 'STOCK_OPNAME':
            case 'STOCK_REPORT':
                return self::parseGenericTable($type, $layout, $grid);
            default:
                return ['rows' => [], 'action' => 'IGNORE', 'notes' => ['unhandled' => $type]];
        }
    }

    /** PAYROLL_RECAP / Copy of REKAP: assoc rows from the R7 header into rekapRow(). Tracks period blocks ("Periode Juli 2026") so repeated names across blocks stay distinct. */
    private static function parsePayrollRecap(array $layout, array $grid, string $sheetName): array
    {
        $rows = [];
        $empCount = 0;
        $block = $layout['title']['period'] ?? 'AGUSTUS 2026';
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            $row = $grid[$rn];
            // period block marker rows ("Periode Juli 2026")
            $line = mb_strtoupper(implode(' ', array_map(fn ($c) => trim($c['v'] ?? ''), $row)));
            if (str_contains($line, 'PERIODE') && preg_match('/([A-Z]+)\s+(\d{4})/', $line, $pm)) {
                $block = $pm[1].' '.$pm[2];

                continue;
            }
            $assoc = [];
            foreach ($layout['headers'] as $col => $name) {
                $assoc[mb_strtoupper(trim($name))] = trim((string) ($row[$col]['v'] ?? ''));
            }
            if (($assoc['NAMA'] ?? '') === '') {
                continue;
            }
            // section label rows mistaken for employees (e.g. "UANG MAKAN PK")
            if (preg_match('/^(UANG MAKAN|TOTAL|JUMLAH)\b/', mb_strtoupper($assoc['NAMA']))) {
                continue;
            }
            $r = BfjRealPayrollParser::rekapRow($assoc, $sheetName, $block);
            foreach ($r['rows'] as $er) {
                $er['row'] = $rn;
                $rows[] = $er;
            }
            foreach ($r['issues'] ?? [] as $iss) {
                $rows[] = [
                    'normalized' => ['dimension' => 'REKAP_ISSUE', 'note' => $iss['message']], 'issues' => [$iss],
                    'fingerprint' => BfjFingerprinter::row(['ri' => $rn, 'm' => $iss['message']]),
                    'status' => $iss['severity'], 'posting_effect' => 'NONE', 'source' => [], 'row' => $rn,
                ];
            }
            $empCount++;
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['employees' => $empCount]];
    }

    /**
     * Transcription-style tables (documents + sparepart PDFs) through the
     * classic field normalizers. STOCK_* sheets become benchmark rows.
     */
    private static function parseGenericTable(string $type, array $layout, array $grid): array
    {
        $rows = [];
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            $row = $grid[$rn];
            $assoc = [];
            foreach ($layout['headers'] as $col => $name) {
                $assoc[trim($name)] = trim((string) ($row[$col]['v'] ?? ''));
            }
            if (count(array_filter($assoc, fn ($v) => $v !== '')) === 0) {
                continue;
            }
            // date-only skeleton rows (reserved entry slots) carry no content
            $content = $assoc;
            unset($content['TANGGAL']);
            if (count(array_filter($content, fn ($v) => $v !== '')) === 0) {
                continue;
            }
            if (in_array($type, ['STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT'], true)) {
                $fp = BfjFingerprinter::row(['t' => $type, 'v' => implode('|', $assoc)]);
                $rows[] = [
                    'normalized' => array_merge(['benchmark_type' => $type], $assoc), 'issues' => [],
                    'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                    'source' => $assoc, 'row' => $rn,
                ];

                continue;
            }
            if ($type === 'SPAREPART_OPENING' || $type === 'SPAREPART_ISSUE') {
                $joined = mb_strtoupper(implode('|', array_keys($assoc)));
                $movement = str_contains($joined, 'KELUAR') ? 'ISSUE' : 'RECEIPT';
                $r = BfjSparepartImporter::normalizeMovement($assoc, $movement);
                $rows[] = [
                    'normalized' => $r['normalized'], 'issues' => $r['issues'],
                    'fingerprint' => $r['normalized']['fingerprint'], 'status' => self::rowStatus($r['issues']),
                    'posting_effect' => 'NONE', 'source' => $assoc, 'row' => $rn,
                ];

                continue;
            }
            $r = match ($type) {
                'CORRESPONDENCE' => BfjDocumentImporter::normalizeLetter($assoc),
                'INVOICE_REGISTER' => BfjDocumentImporter::normalizeInvoice($assoc),
                'RECEIPT_REGISTER' => BfjDocumentImporter::normalizeReceipt($assoc),
                default => BfjSparepartImporter::normalizeMaster($assoc),
            };
            $n = $r['normalized'];
            $fp = BfjFingerprinter::row(['t' => $type, 'v' => implode('|', array_map(fn ($v) => is_array($v) ? json_encode($v) : (string) $v, $n))]);
            $rows[] = [
                'normalized' => $n, 'issues' => $r['issues'], 'fingerprint' => $fp,
                'status' => self::rowStatus($r['issues']), 'posting_effect' => 'NONE',
                'source' => $assoc, 'row' => $rn,
            ];
        }
        $action = in_array($type, ['STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT'], true) ? 'RECONCILE_ONLY' : 'IMPORT';

        return ['rows' => $rows, 'action' => $action, 'notes' => ['transcription' => $type]];
    }

    private static function rowStatus(array $issues): string
    {
        if (BfjRealCommon::any($issues, fn ($i) => $i['severity'] === 'ERROR')) {
            return 'ERROR';
        }

        return $issues === [] ? 'READY' : 'WARNING';
    }

    /** Stage layout-parser envelope rows (they carry their own status + source row). */
    private static function stageEnvelope(LegacyImportBatch $batch, LegacyImportSheet $sheet, array $envelope): void
    {
        $seen = [];
        foreach (array_chunk($envelope['rows'], 500) as $chunk) {
            $payload = [];
            foreach ($chunk as $er) {
                $fp = $er['fingerprint'];
                $status = $er['status'];
                $rowNum = $er['row'] ?? 0;
                foreach ($er['issues'] ?? [] as $iss) {
                    self::issue($batch, $sheet->id, $rowNum ?: null, $iss['code'], $iss['severity'], $iss['message']);
                }
                if (isset($seen[$fp])) {
                    $status = 'DUPLICATE';
                    self::issue($batch, $sheet->id, $rowNum ?: null, 'DUPLICATE_REFERENCE', 'WARNING', 'Duplicate row fingerprint in source');
                }
                $seen[$fp] = true;
                self::recordMatches($batch->id, $sheet->detected_type ?? '', $er['normalized']);
                $payload[] = [
                    'sheet_id' => $sheet->id, 'row_number' => $rowNum,
                    'source' => json_encode($er['source'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                    'normalized' => json_encode($er['normalized'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                    'fingerprint' => $fp, 'status' => $status, 'posting_effect' => $er['posting_effect'] ?? 'NONE',
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            if ($payload !== []) {
                LegacyImportRow::insert($payload);
            }
        }
    }

    /** @return array{0:array,1:array,2:string} */
    private static function normalizeRowLegacy(string $type, array $assoc, float $tolerance): array
    {
        return match ($type) {
            'SALES' => (function () use ($assoc, $tolerance) {
                $r = BfjSalesImporter::normalize($assoc, $tolerance);

                return [$r['normalized'], $r['issues'], $r['normalized']['fingerprint']];
            })(),
            'CUSTOMER_DEPOSIT' => (function () use ($assoc) {
                $r = BfjDepositImporter::normalize($assoc);

                return [$r['normalized'], $r['issues'], $r['normalized']['fingerprint']];
            })(),
            'FINANCE' => (function () use ($assoc) {
                $r = BfjFinanceImporter::normalize($assoc);
                $n = $r['normalized'];
                $fp = BfjFingerprinter::row(['d' => $n['date'] ?? '', 'x' => $n['description'] ?? '', 'i' => $n['inflow'] ?? 0, 'o' => $n['outflow'] ?? 0]);

                return [$n, $r['issues'], $fp];
            })(),
            'INVOICE_REGISTER' => (function () use ($assoc) {
                $r = BfjDocumentImporter::normalizeInvoice($assoc);
                $n = $r['normalized'];
                $fp = BfjFingerprinter::row(['n' => $n['number'] ?? '', 'd' => $n['date'] ?? '', 't' => $n['total'] ?? 0, 'c' => $n['customer'] ?? '', 's' => $n['status'] ?? '']);

                return [$n, $r['issues'], $fp];
            })(),
            'RECEIPT_REGISTER' => (function () use ($assoc) {
                $r = BfjDocumentImporter::normalizeReceipt($assoc);
                $n = $r['normalized'];
                $fp = BfjFingerprinter::row(['n' => $n['number'] ?? '', 'd' => $n['date'] ?? '', 'a' => $n['amount'] ?? 0, 'm' => $n['method'] ?? '', 'i' => $n['invoice_ref'] ?? '']);

                return [$n, $r['issues'], $fp];
            })(),
            'CORRESPONDENCE' => (function () use ($assoc) {
                $r = BfjDocumentImporter::normalizeLetter($assoc);
                $n = $r['normalized'];
                $fp = BfjFingerprinter::row(['n' => $n['number'] ?? '', 'd' => $n['date'] ?? '', 's' => $n['subject'] ?? '', 'r' => $n['recipient'] ?? '']);

                return [$n, $r['issues'], $fp];
            })(),
            'SPAREPART_MASTER' => (function () use ($assoc) {
                $r = BfjSparepartImporter::normalizeMaster($assoc);
                $fp = BfjFingerprinter::row(['c' => $r['normalized']['code'] ?? '', 'n' => $r['normalized']['name'] ?? '']);

                return [$r['normalized'], $r['issues'], $fp];
            })(),
            'SPAREPART_OPENING', 'SPAREPART_ISSUE', 'STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT' => (function () use ($assoc, $type) {
                // date-only skeleton rows (reserved entry slots) carry no content
                $content = $assoc;
                unset($content['TANGGAL']);
                if (count(array_filter($content, fn ($v) => trim((string) ($v ?? '')) !== '')) === 0) {
                    return [[], [['code' => 'SKIP_ROW', 'severity' => 'INFO', 'message' => 'date-only skeleton row']], 'skip'];
                }
                $r = BfjSparepartImporter::normalizeMovement($assoc, $type === 'SPAREPART_ISSUE' ? 'ISSUE' : 'RECEIPT');

                return [$r['normalized'], $r['issues'], $r['normalized']['fingerprint']];
            })(),
            default => (function () use ($assoc) {
                $r = BfjPayrollImporter::normalizeDay($assoc);
                $fp = BfjFingerprinter::row(['r' => json_encode($assoc)]);

                return [array_merge($assoc, ['normal_hours' => $r['normal_hours'], 'overtime' => $r['overtime']]), $r['issues'], $fp];
            })(),
        };
    }

    private static function recordMatches(int $batchId, string $type, array $n): void
    {
        $entities = match ($type) {
            'SALES' => ['CUSTOMER' => $n['customer_legacy'] ?? null, 'VEHICLE' => $n['vehicle_legacy'] ?? null, 'EMPLOYEE' => $n['driver'] ?? null, 'MATERIAL' => $n['material_legacy'] ?? null],
            'SALES_RECAP_MATRIX', 'SALES_RETAIL_MATRIX' => ['CUSTOMER' => $n['customer'] ?? null, 'MATERIAL' => $n['material'] ?? null],
            'CUSTOMER_DEPOSIT' => [
                'CUSTOMER' => $n['customer_legacy'] ?? $n['customer'] ?? null,
                'VEHICLE' => $n['vehicle_legacy'] ?? null, 'EMPLOYEE' => $n['driver'] ?? null,
                'MATERIAL' => $n['material_legacy'] ?? $n['material'] ?? null,
            ],
            'DEPOSIT_SISA' => ['CUSTOMER' => $n['customer_legacy'] ?? null],
            'SPAREPART_MASTER', 'SPAREPART_OPENING', 'SPAREPART_ISSUE' => [
                'SPAREPART' => $n['code'] ?? null, 'EMPLOYEE' => $n['user'] ?? null, 'EQUIPMENT' => $n['equipment'] ?? null,
            ],
            'PAYROLL_DAY' => ['EMPLOYEE' => $n['employee'] ?? null, 'EQUIPMENT' => $n['unit'] ?? null],
            'PAYROLL_RECAP', 'PAYROLL_SECURITY' => ['EMPLOYEE' => $n['employee'] ?? null],
            'INVOICE_REGISTER', 'RECEIPT_REGISTER' => ['CUSTOMER' => $n['customer'] ?? $n['payer'] ?? null],
            default => [],
        };
        foreach ($entities as $entity => $val) {
            if (! $val || trim((string) $val) === '') {
                continue;
            }
            $match = BfjMasterMatcher::match($entity, (string) $val);
            // avoid flooding: one match row per distinct legacy value
            if (! LegacyImportMatch::where('batch_id', $batchId)->where('entity_type', $entity)->where('legacy_value', mb_substr((string) $val, 0, 160))->exists()) {
                BfjMasterMatcher::record($batchId, $entity, (string) $val, $match);
            }
        }
    }

    /** @return array{type:string,confidence:int,is_summary:bool} */
    private static function classifyFile(string $storedPath, array $sheets): array
    {
        return BfjClassifier::classifyFile(basename($storedPath), $sheets);
    }

    private static function issue(LegacyImportBatch $batch, ?int $sheetId, ?int $row, string $code, string $severity, string $msg): void
    {
        LegacyImportIssue::create(['batch_id' => $batch->id, 'sheet_id' => $sheetId, 'row_number' => $row, 'code' => $code, 'severity' => $severity, 'message' => mb_substr($msg, 0, 1000)]);
    }

    /** Validate staged rows → READY counts. */
    public static function validate(LegacyImportBatch $batch): array
    {
        $ready = LegacyImportRow::whereHas('sheet', fn ($q) => $q->where('batch_id', $batch->id))->where('status', 'READY')->count();
        $warn = LegacyImportRow::whereHas('sheet', fn ($q) => $q->where('batch_id', $batch->id))->where('status', 'WARNING')->count();
        $err = LegacyImportRow::whereHas('sheet', fn ($q) => $q->where('batch_id', $batch->id))->where('status', 'ERROR')->count();
        $dup = LegacyImportRow::whereHas('sheet', fn ($q) => $q->where('batch_id', $batch->id))->where('status', 'DUPLICATE')->count();
        $batch->update(['status' => $err > 0 ? 'VALIDATED' : 'READY', 'totals' => array_merge($batch->totals ?? [], ['ready' => $ready, 'warning' => $warn, 'error' => $err, 'duplicate' => $dup])]);

        return ['ready' => $ready, 'warning' => $warn, 'error' => $err, 'duplicate' => $dup];
    }

    /** Impact simulation before import (§90). No writes. */
    public static function impact(LegacyImportBatch $batch): array
    {
        $q = LegacyImportRow::whereHas('sheet', fn ($qq) => $qq->where('batch_id', $batch->id)->where('action', 'IMPORT'))->whereIn('status', ['READY', 'WARNING']);
        $toCreate = (clone $q)->count();
        $toSkip = LegacyImportRow::whereHas('sheet', fn ($qq) => $qq->where('batch_id', $batch->id))->whereIn('status', ['ERROR', 'DUPLICATE', 'SKIPPED'])->count();
        $needsPosting = $batch->allow_accounting_posting || $batch->allow_stock_posting;
        $unresolved = LegacyImportMatch::where('batch_id', $batch->id)->whereIn('status', ['POSSIBLE_MATCH', 'NEW_MASTER_REQUIRED'])->count();

        return [
            'records_to_create' => $toCreate, 'records_to_link' => 0, 'records_to_skip' => $toSkip,
            'stock_impact' => $batch->allow_stock_posting ? $toCreate : 0,
            'ar_impact' => 0, 'ap_impact' => 0, 'deposit_impact' => 0, 'payroll_impact' => 0,
            'journal_impact' => $batch->allow_accounting_posting ? $toCreate : 0,
            'requires_confirmation' => $needsPosting || $unresolved > 0,
            'unresolved_masters' => $unresolved,
        ];
    }

    /**
     * Import staged rows. Default HISTORY_ONLY: no stock/accounting writes.
     * Idempotent: fingerprints + legacy_do_number guards skip duplicates.
     */
    public static function import(LegacyImportBatch $batch, bool $force = false): array
    {
        $batch->update(['status' => 'IMPORTING']);
        $imported = 0;
        $skipped = 0;
        $snapshotBefore = self::snapshot();

        $sheets = $batch->sheets()->where('action', 'IMPORT')->get();
        foreach ($sheets as $sheet) {
            $rows = $sheet->rows()->whereIn('status', $force ? ['READY', 'WARNING', 'ERROR'] : ['READY', 'WARNING'])->orderBy('id')->cursor();
            foreach ($rows as $row) {
                try {
                    $done = self::persistRow($batch, $sheet, $row);
                    $row->update(['status' => 'IMPORTED']);
                    $done ? $imported++ : $skipped++;
                } catch (\Throwable $e) {
                    $row->update(['status' => 'ERROR']);
                    $code = str_starts_with($e->getMessage(), 'UNRESOLVED_') ? explode(':', $e->getMessage())[0] : 'MISSING_FIELD';
                    self::issue($batch, $sheet->id, $row->row_number, $code, 'ERROR', mb_substr($e->getMessage(), 0, 500));
                    $skipped++;
                }
            }
        }
        $batch->update([
            'status' => $skipped > 0 && $imported > 0 ? 'PARTIAL' : ($imported > 0 ? 'IMPORTED' : 'FAILED'),
            'totals' => array_merge($batch->totals ?? [], ['imported' => $imported, 'skipped' => $skipped, 'snapshot_before' => $snapshotBefore, 'snapshot_after' => self::snapshot()]),
        ]);
        try {
            AuditService::log('IMPORT', 'LEGACY_IMPORT', $batch->id, LegacyImportBatch::class, null, ['imported' => $imported, 'skipped' => $skipped]);
        } catch (\Throwable) {
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private static function persistRow(LegacyImportBatch $batch, LegacyImportSheet $sheet, LegacyImportRow $row): bool
    {
        $n = $row->normalized ?? [];
        // cutoff: historical records before cutoff stay history-only (§86)
        if ($batch->cutoff_date && isset($n['transaction_date']) && $n['transaction_date'] < $batch->cutoff_date->format('Y-m-d')) {
            $row->update(['posting_effect' => 'NONE']);

            return true;
        }

        return DB::transaction(function () use ($batch, $sheet, $row, $n) {
            $type = $sheet->detected_type ?? '';
            switch ($type) {
                case 'SPAREPART_MASTER':
                    self::persistSparepartMaster($batch, $n, $sheet, $row);
                    $row->update(['posting_effect' => 'NONE']);

                    return true;
                case 'SPAREPART_OPENING':
                case 'SPAREPART_ISSUE':
                    if (! $batch->allow_stock_posting) {
                        $row->update(['posting_effect' => 'NONE']);

                        return true; // history-only
                    }
                    self::persistStockMovement($batch, $n, $sheet, $row);
                    $row->update(['posting_effect' => 'STOCK']);

                    return true;
                case 'CORRESPONDENCE':
                    self::persistLetter($batch, $n, $sheet, $row);
                    $row->update(['posting_effect' => 'NONE']);

                    return true;
                case 'INVOICE_REGISTER':
                    if (($batch->mode ?? '') === 'REGISTER_ONLY' || ! $batch->allow_accounting_posting) {
                        self::persistInvoiceHistory($batch, $n, $sheet, $row, false);
                        $row->update(['posting_effect' => 'NONE']);

                        return true;
                    }
                    self::persistInvoiceHistory($batch, $n, $sheet, $row, true);
                    $row->update(['posting_effect' => 'ACCOUNTING']);

                    return true;
                case 'RECEIPT_REGISTER':
                    self::persistReceiptHistory($batch, $n, $sheet, $row);
                    $row->update(['posting_effect' => 'NONE']);

                    return true;
                case 'SALES':
                    // HISTORY_ONLY default: link DO if exact match, else history row only (§10, §14)
                    // Accepted rows stay staging with posting NONE — no stock/AR/journal effect.
                    self::linkDeliveryOrder($batch, $n, $sheet, $row);
                    $row->update(['posting_effect' => 'NONE']);

                    return true;
                default:
                    // DEPOSIT / FINANCE / PAYROLL / STOCK benchmarks: staging only unless explicit posting flags
                    $row->update(['posting_effect' => 'NONE']);

                    return true;
            }
        });
    }

    private static function persistSparepartMaster(LegacyImportBatch $batch, array $n, LegacyImportSheet $sheet, LegacyImportRow $row): void
    {
        if (($n['code'] ?? '') === '') {
            throw new \DomainException('Kode kosong');
        }
        if (Item::where('code', $n['code'])->exists()) {
            return;
        }
        $cat = ItemCategory::firstOrCreate(['code' => 'SPAREPART'], ['name' => 'Sparepart', 'type' => 'SPAREPART']);
        $unit = Unit::firstOrCreate(['code' => ($n['unit'] ?: 'PCS')], ['name' => $n['unit'] ?: 'Pcs']);
        $item = Item::create([
            'code' => $n['code'], 'name' => $n['name'] ?: $n['code'],
            'item_category_id' => $cat->id, 'type' => 'SPAREPART', 'unit_id' => $unit->id,
            'status' => true, 'source' => 'LEGACY_IMPORT', 'legacy_reference' => $n['code'],
        ]);
        $row->update(['reference_type' => Item::class, 'reference_id' => $item->id]);
        self::link($batch, $item, $sheet, $row, $n['code']);
    }

    private static function persistStockMovement(LegacyImportBatch $batch, array $n, LegacyImportSheet $sheet, LegacyImportRow $row): void
    {
        $item = Item::where('code', $n['code'] ?? '')->first();
        if (! $item || ! ($n['qty'] ?? null)) {
            throw new \DomainException('Item/qty tidak valid');
        }
        $warehouseId = $batch->warehouse_id ?? Warehouse::query()->value('id');
        if (! $warehouseId) {
            throw new \DomainException('Warehouse belum dikonfigurasi');
        }
        $qty = abs((float) $n['qty']);
        $isIn = ($n['movement'] ?? '') === 'OPENING_BALANCE' || $sheet->detected_type === 'SPAREPART_OPENING';
        $ledger = StockLedger::create([
            'warehouse_id' => $warehouseId, 'item_id' => $item->id,
            'trx_date' => $n['date'] ?? now()->format('Y-m-d'),
            'movement_type' => ($n['movement'] ?? '') === 'OPENING_BALANCE' ? 'OPENING' : ($isIn ? 'IN' : 'OUT'),
            'qty_in' => $isIn ? $qty : 0, 'qty_out' => $isIn ? 0 : $qty,
            'unit_cost' => 0, 'total_cost' => 0,
            'ref_type' => 'LEGACY_IMPORT', 'ref_id' => $batch->id, 'ref_number' => $n['code'] ?? null,
            'company_id' => $batch->company_id, 'site_id' => $batch->site_id,
            'source' => 'LEGACY_IMPORT', 'import_batch_id' => null, 'notes' => $n['purpose'] ?? null,
            'legacy_reference' => $n['code'] ?? null,
        ]);
        $row->update(['reference_type' => StockLedger::class, 'reference_id' => $ledger->id]);
        self::link($batch, $ledger, $sheet, $row, $n['code'] ?? null);
    }

    private static function persistLetter(LegacyImportBatch $batch, array $n, LegacyImportSheet $sheet, LegacyImportRow $row): void
    {
        if (($n['number'] ?? '') === '') {
            throw new \DomainException('Nomor surat kosong');
        }
        if (LetterRegister::where('number', $n['number'])->exists()) {
            return;
        }
        $company = $batch->company_id ? Company::find($batch->company_id) : Company::first();
        $letter = LetterRegister::create([
            'number' => $n['number'], 'subject' => $n['subject'] ?: $n['number'],
            'recipient_type' => 'OTHER', 'recipient_name' => $n['recipient'] ?? null,
            'letter_date' => $n['date'] ?? now()->format('Y-m-d'),
            'letter_type_id' => LetterType::query()->value('id') ?? LetterType::create(['code' => 'LEGACY', 'name' => 'Legacy'])->id,
            'company_id' => $company?->id, 'site_id' => $batch->site_id,
            'status' => 'ARCHIVED', 'source' => 'LEGACY_IMPORT', 'legacy_reference' => $batch->file_name,
            'created_by' => auth()->id() ?? User::query()->value('id'),
        ]);
        $row->update(['reference_type' => LetterRegister::class, 'reference_id' => $letter->id]);
        self::link($batch, $letter, $sheet, $row, $n['number']);
    }

    private static function persistInvoiceHistory(LegacyImportBatch $batch, array $n, LegacyImportSheet $sheet, LegacyImportRow $row, bool $postAr): void
    {
        if (($n['number'] ?? '') === '') {
            throw new \DomainException('Nomor invoice kosong');
        }
        if (Invoice::where('number', $n['number'])->exists()) {
            return;
        }
        $customer = isset($n['customer']) ? Customer::query()->get()->first(fn ($c) => BfjNormalizer::normalizeCustomer($c->name ?? '') === $n['customer']) : null;
        if (! $customer) {
            // never silently create production masters: queue via Master Mapping, import after resolution
            throw new \DomainException('UNRESOLVED_CUSTOMER: '.($n['customer'] ?? '(blank)').' — resolve in Master Mapping, then re-import');
        }
        $company = $batch->company_id ? Company::find($batch->company_id) : Company::first();
        $invoice = Invoice::create([
            'number' => $n['number'], 'company_id' => $company?->id,
            'customer_id' => $customer?->id, 'invoice_date' => $n['date'] ?? now()->format('Y-m-d'),
            'subtotal' => $n['total'] ?? 0, 'tax_amount' => 0, 'total' => $n['total'] ?? 0,
            'paid_amount' => ($n['status'] ?? '') === 'PAID' ? ($n['total'] ?? 0) : 0,
            'status' => 'DRAFT', 'source' => 'LEGACY_IMPORT', 'legacy_reference' => $batch->file_name,
            'created_by' => auth()->id() ?? User::query()->value('id'),
        ]);
        $row->update(['reference_type' => Invoice::class, 'reference_id' => $invoice->id]);
        self::link($batch, $invoice, $sheet, $row, $n['number']);
    }

    private static function persistReceiptHistory(LegacyImportBatch $batch, array $n, LegacyImportSheet $sheet, LegacyImportRow $row): void
    {
        if (($n['number'] ?? '') === '') {
            throw new \DomainException('Nomor kwitansi kosong');
        }
        if (Receipt::where('number', $n['number'])->exists()) {
            return;
        }
        $invoice = ($n['invoice_ref'] ?? '') !== '' ? Invoice::where('number', $n['invoice_ref'])->first() : null;
        $receipt = Receipt::create([
            'number' => $n['number'], 'receipt_date' => $n['date'] ?? now()->format('Y-m-d'),
            'company_id' => $batch->company_id, 'invoice_id' => $invoice?->id,
            'customer_id' => $invoice?->customer_id, 'amount' => $n['amount'] ?? 0,
            'payment_method' => in_array($n['method'] ?? '', ['CASH', 'TRANSFER', 'GIRO', 'DEPOSIT']) ? $n['method'] : 'CASH',
            'status' => 'ISSUED', 'source' => 'LEGACY_IMPORT', 'legacy_reference' => $n['number'],
        ]);
        $row->update(['reference_type' => Receipt::class, 'reference_id' => $receipt->id]);
        self::link($batch, $receipt, $sheet, $row, $n['number']);
    }

    private static function linkDeliveryOrder(LegacyImportBatch $batch, array $n, LegacyImportSheet $sheet, LegacyImportRow $row): void
    {
        if (($n['legacy_do_number'] ?? '') === '') {
            return;
        }
        try {
            $do = DeliveryOrder::where('number', $n['legacy_do_number'])->first();
            if ($do) {
                $row->update(['reference_type' => DeliveryOrder::class, 'reference_id' => $do->id]);
                self::link($batch, $do, $sheet, $row, $n['legacy_do_number']);
            }
        } catch (\Throwable) {
        }
    }

    private static function link(LegacyImportBatch $batch, mixed $model, LegacyImportSheet $sheet, LegacyImportRow $row, ?string $legacyRef): void
    {
        try {
            LegacyImportLink::create([
                'batch_id' => $batch->id, 'reference_type' => $model::class, 'reference_id' => $model->id,
                'source_sheet' => $sheet->sheet_name, 'source_row' => $row->row_number,
                'legacy_reference' => $legacyRef, 'source_hash' => $row->fingerprint,
            ]);
        } catch (\Throwable) {
        }
    }

    /** @return array<string,int|float> */
    private static function snapshot(): array
    {
        try {
            return [
                'invoices' => Invoice::count(), 'receipts' => Receipt::count(),
                'stock_rows' => StockLedger::count(), 'letters' => LetterRegister::count(),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /** Rollback history-only rows; posted effects must reverse, never hard-delete (§111). */
    public static function rollback(LegacyImportBatch $batch): array
    {
        $deleted = 0;
        foreach ($batch->sheets as $sheet) {
            foreach ($sheet->rows()->where('status', 'IMPORTED')->cursor() as $row) {
                if ($row->posting_effect !== 'NONE' && $row->posting_effect !== null) {
                    self::issue($batch, $sheet->id, $row->row_number, 'MISSING_FIELD', 'WARNING', 'Posted effect requires reversal, not delete');

                    continue;
                }
                if ($row->reference_type && $row->reference_id && class_exists($row->reference_type)) {
                    try {
                        $m = $row->reference_type::find($row->reference_id);
                        if ($m && ($m->source ?? null) === 'LEGACY_IMPORT') {
                            $m->delete();
                            $deleted++;
                        }
                    } catch (\Throwable) {
                    }
                }
                $row->update(['status' => 'SKIPPED']);
            }
        }
        $batch->update(['status' => 'ROLLED_BACK']);
        try {
            AuditService::log('ROLLBACK', 'LEGACY_IMPORT', $batch->id, LegacyImportBatch::class, null, ['deleted' => $deleted]);
        } catch (\Throwable) {
        }

        return ['deleted' => $deleted];
    }

    /**
     * Re-import diff: NEW / UNCHANGED / CHANGED / REMOVED_FROM_SOURCE (§76, §117).
     * CHANGED = same business key, different fingerprint. Never deletes ERP
     * records for removed source rows — reported only.
     *
     * @return array{NEW:array,UNCHANGED:array,CHANGED:array,REMOVED_FROM_SOURCE:array}
     */
    public static function diff(int $oldBatchId, int $newBatchId): array
    {
        $out = ['NEW' => [], 'UNCHANGED' => [], 'CHANGED' => [], 'REMOVED_FROM_SOURCE' => []];
        $collect = function (int $bid): array {
            $map = [];
            foreach (LegacyImportBatch::find($bid)?->sheets ?? [] as $sheet) {
                foreach ($sheet->rows()->get() as $row) {
                    $n = $row->normalized ?? [];
                    $key = self::diffKey($sheet->detected_type ?? '', $n) ?? $row->fingerprint;
                    $map[$sheet->sheet_name.'|'.$key] = ['sheet' => $sheet->sheet_name, 'row' => $row->row_number, 'key' => $key, 'fingerprint' => $row->fingerprint];
                }
            }

            return $map;
        };
        $old = $collect($oldBatchId);
        $new = $collect($newBatchId);
        foreach ($new as $k => $v) {
            if (! isset($old[$k])) {
                $out['NEW'][] = $v;
            } elseif ($old[$k]['fingerprint'] === $v['fingerprint']) {
                $out['UNCHANGED'][] = $v;
            } else {
                $out['CHANGED'][] = $v;
            }
        }
        foreach ($old as $k => $v) {
            if (! isset($new[$k])) {
                $out['REMOVED_FROM_SOURCE'][] = $v;
            }
        }

        return $out;
    }

    private static function diffKey(string $type, array $n): ?string
    {
        return match ($type) {
            'CORRESPONDENCE' => ($n['number'] ?? '') !== '' ? 'NUM:'.$n['number'] : null,
            'INVOICE_REGISTER' => ($n['number'] ?? '') !== '' ? 'NUM:'.$n['number'] : null,
            'RECEIPT_REGISTER' => ($n['number'] ?? '') !== '' ? 'NUM:'.$n['number'] : null,
            'SPAREPART_MASTER' => ($n['code'] ?? '') !== '' ? 'CODE:'.$n['code'] : null,
            'SALES' => 'SALE:'.($n['transaction_date'] ?? '').'|'.($n['legacy_do_number'] ?? '').'|'.($n['customer'] ?? ''),
            'CUSTOMER_DEPOSIT' => 'DEP:'.($n['date'] ?? '').'|'.($n['legacy_do_number'] ?? '').'|'.($n['customer'] ?? ''),
            'FINANCE_DETAIL' => 'FIN:'.($n['date'] ?? '').'|'.($n['description'] ?? ''),
            'PAYROLL_DAY' => 'PAY:'.($n['employee'] ?? '').'|'.($n['date'] ?? ''),
            default => null,
        };
    }

    /** @return array<string,mixed> */
    public static function report(LegacyImportBatch $batch): array    {
        $rows = LegacyImportRow::whereHas('sheet', fn ($q) => $q->where('batch_id', $batch->id));
        $recs = $batch->reconciliations;

        return [
            'file' => $batch->file_name, 'sheets' => $batch->sheets->count(),
            'rows' => (clone $rows)->count(),
            'valid' => (clone $rows)->where('status', 'READY')->count(),
            'warning' => (clone $rows)->where('status', 'WARNING')->count(),
            'error' => (clone $rows)->where('status', 'ERROR')->count(),
            'duplicate' => (clone $rows)->where('status', 'DUPLICATE')->count(),
            'imported' => (clone $rows)->where('status', 'IMPORTED')->count(),
            'unresolved_masters' => $batch->matches()->whereIn('status', ['POSSIBLE_MATCH', 'NEW_MASTER_REQUIRED'])->count(),
            'reconciliation_pass' => $recs->where('status', 'MATCH')->count(),
            'reconciliation_fail' => $recs->where('status', 'VARIANCE')->count(),
            'financial_impact' => $batch->allow_accounting_posting ? 'POSTING_ENABLED' : 'NONE',
            'stock_impact' => $batch->allow_stock_posting ? 'POSTING_ENABLED' : 'NONE',
        ];
    }
}
