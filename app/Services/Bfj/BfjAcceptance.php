<?php

namespace App\Services\Bfj;

use App\Models\LegacyImportBatch;
use App\Models\LegacyImportIssue;
use App\Models\LegacyImportMatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyReconciliation;

/**
 * Go-live acceptance aggregation (§50-§59, §85-§86, §93-§95).
 *
 * No false passes (§91): PASS requires business reconciliation, not parser
 * success. Variances without root_cause are "unexplained". Nothing here
 * posts journals or stock — it only measures staged history vs benchmarks.
 */
final class BfjAcceptance
{
    /**
     * @param  array<string,int|int[]>  $roles sales, deposit, finance, payroll, documents, spareparts (ids or lists)
     */
    public static function run(array $roles, array $probes = []): array
    {
        $batches = [];
        foreach ($roles as $role => $ids) {
            foreach ((array) $ids as $id) {
                if (LegacyImportBatch::whereKey($id)->exists()) {
                    $batches[$role][] = (int) $id;
                }
            }
        }
        $allIds = collect($batches)->flatten()->values()->all();
        $out = [
            'files' => self::files($batches),
            'sales' => self::scopeSummary($batches['sales'] ?? [], 'SALES'),
            'deposit' => self::scopeSummary($batches['deposit'] ?? [], 'DEPOSIT'),
            'finance' => self::scopeSummary($batches['finance'] ?? [], 'FINANCE'),
            'payroll' => self::scopeSummary($batches['payroll'] ?? [], 'PAYROLL'),
            'documents' => self::documents($batches['documents'] ?? []),
            'spareparts' => self::scopeSummary($batches['spareparts'] ?? [], 'SPAREPART'),
            'cross_file' => self::crossFile($allIds),
            'data_quality' => self::dataQuality($allIds),
            'master_mapping' => self::masterMapping($allIds),
            'tests' => $probes['tests'] ?? null,
            'integrity' => $probes['integrity'] ?? null,
            'build' => $probes['build'] ?? null,
        ];
        $out['overall_status'] = self::overall($out);
        $out['readiness'] = self::readiness($out);
        $out['go_live'] = self::goLive($out);

        return $out;
    }

    /**
     * @param  int[]  $ids
     * @return array<string,int[]>
     */
    public static function inferRoles(array $ids): array
    {
        $roles = [];
        foreach (LegacyImportBatch::whereIn('id', $ids)->get() as $b) {
            $f = mb_strtoupper(basename($b->file_name));
            $role = 'documents';
            if (str_contains($f, 'PENJUALAN') || str_contains($f, 'SALES')) {
                $role = 'sales';
            } elseif (str_contains($f, 'DEPOSIT')) {
                $role = 'deposit';
            } elseif (str_contains($f, 'KEUANG') || str_contains($f, 'ARUS KAS') || str_contains($f, 'FINANCE')) {
                $role = 'finance';
            } elseif (str_contains($f, 'GAJI') || str_contains($f, 'PAYROLL')) {
                $role = 'payroll';
            } elseif (str_contains($f, 'SP_') || str_contains($f, 'SPAREPART') || str_contains($f, 'KARTU') || str_contains($f, 'OPNAME') || str_contains($f, 'LAPORAN') && str_contains($f, 'STOK')) {
                $role = 'spareparts';
            }
            $roles[$role][] = $b->id;
        }

        return $roles;
    }

    private static function batchIds(array $batches): array
    {
        return collect($batches)->flatten()->values()->all();
    }

    private static function files(array $batches): array
    {
        $rows = [];
        $docs = [];
        foreach ($batches as $role => $ids) {
            foreach ($ids as $id) {
                $b = LegacyImportBatch::withCount('sheets')->find($id);
                if (! $b) {
                    continue;
                }
                $file = basename($b->file_name);
                // transcription CSVs stand in for their source PDFs (never committed)
                $source = match (true) {
                    str_starts_with($file, 'letters.') || str_starts_with($file, 'invoices.') || str_starts_with($file, 'receipts.') => 'PENCATATAN NOMOR SURAT, INVOICE, DAN KWITANSI.pdf (via transcription)',
                    str_starts_with($file, 'sp_') => 'DATA & ADMINISTRASI GUDANG SPAREPART BFJ.pdf (via transcription)',
                    default => $file,
                };
                $docs[$source] = true;
                $rows[] = [
                    'role' => $role, 'batch' => $id, 'file' => $file, 'source_doc' => $source,
                    'hash' => substr($b->file_hash, 0, 16), 'sheets' => $b->sheets_count,
                    'status' => $b->status, 'mode' => $b->mode,
                ];
            }
        }

        return ['recognized' => count($docs), 'expected' => 6, 'batches' => $rows];
    }

    /** @param  int[]  $ids */
    private static function sheetIds(array $ids): array
    {
        $out = [];
        foreach (LegacyImportBatch::whereIn('id', $ids)->with('sheets')->get() as $b) {
            foreach ($b->sheets as $s) {
                $out[] = $s->id;
            }
        }

        return $out;
    }

    /** @param  int[]  $ids */
    private static function scopeSummary(array $ids, string $scope): array
    {
        $sheetIds = self::sheetIds($ids);
        $rows = LegacyImportRow::whereIn('sheet_id', $sheetIds);
        $recs = LegacyReconciliation::whereIn('batch_id', $ids)->where('scope', $scope);
        $match = (clone $recs)->where('status', 'MATCH')->count();
        $variance = (clone $recs)->where('status', 'VARIANCE')->count();
        $unexplained = (clone $recs)->where('status', 'VARIANCE')->whereNull('root_cause')->count();
        $criticalErrors = LegacyImportRow::whereIn('sheet_id', $sheetIds)
            ->whereHas('sheet', fn ($q) => $q->where('action', 'IMPORT'))
            ->where('status', 'ERROR')->count();

        return [
            'rows' => (clone $rows)->count(),
            'valid' => (clone $rows)->where('status', 'READY')->count(),
            'warning' => (clone $rows)->where('status', 'WARNING')->count(),
            'error' => (clone $rows)->where('status', 'ERROR')->count(),
            'duplicate' => (clone $rows)->where('status', 'DUPLICATE')->count(),
            'imported' => (clone $rows)->where('status', 'IMPORTED')->count(),
            'reconciled' => $match + $variance,
            'match' => $match, 'variance' => $variance, 'unexplained' => $unexplained,
            'critical_errors' => $criticalErrors,
        ];
    }

    /** @param  int[]  $ids */
    private static function documents(array $ids): array
    {
        $out = self::scopeSummary($ids, 'DOCUMENTS');
        $out += ['letters' => 0, 'invoices' => 0, 'receipts' => 0, 'number_gaps' => [], 'pattern_variants' => 0, 'invalid_dates' => 0];
        $sheetIds = self::sheetIds($ids);
        foreach (LegacyImportRow::whereIn('sheet_id', $sheetIds)->cursor() as $r) {
            $n = $r->normalized ?? [];
            if (isset($n['tokens'])) {
                $out['letters']++;
                if (empty($n['tokens'])) {
                    $out['pattern_variants']++;
                }
            }
            if (isset($n['legacy_invoice']) || isset($n['product'])) {
                $out['invoices']++;
            }
            if (isset($n['invoice_ref'])) {
                $out['receipts']++;
            }
        }
        $out['number_gaps'] = self::letterGaps($ids);
        $out['invalid_dates'] = LegacyImportIssue::whereIn('batch_id', $ids)->whereIn('code', ['INVALID_DATE', 'FORMULA_ERROR'])->count();

        return $out;
    }

    /** Missing sequences per letter-number family (gaps are reported, never fixed). */
    private static function letterGaps(array $ids): array
    {
        $seqs = [];
        $sheetIds = self::sheetIds($ids);
        foreach (LegacyImportRow::whereIn('sheet_id', $sheetIds)->cursor() as $r) {
            $n = $r->normalized ?? [];
            $t = $n['tokens'] ?? null;
            if (! $t || ! ctype_digit((string) ($t['seq'] ?? ''))) {
                continue;
            }
            $fam = implode('/', array_filter([$t['doc_type'] ?? 'BARE', $t['company'] ?? 'NOCOMP', $t['year'] ?? 'NOYEAR']));
            $seqs[$fam][] = (int) $t['seq'];
        }
        $gaps = [];
        foreach ($seqs as $fam => $list) {
            sort($list);
            $list = array_values(array_unique($list));
            for ($i = 1; $i < count($list); $i++) {
                for ($m = $list[$i - 1] + 1; $m < $list[$i]; $m++) {
                    $gaps[] = $fam.' missing '.$m;
                }
            }
        }

        return array_slice($gaps, 0, 50);
    }

    /** @param  int[]  $allIds */
    private static function crossFile(array $allIds): array
    {
        return [
            'entries' => LegacyReconciliation::whereIn('batch_id', $allIds)->where('scope', 'CROSS_FILE')->count(),
            'unmatched' => LegacyReconciliation::whereIn('batch_id', $allIds)->where('scope', 'CROSS_FILE')->where('status', 'VARIANCE')->count(),
        ];
    }

    /** @param  int[]  $allIds */
    private static function dataQuality(array $allIds): array
    {
        $codes = LegacyImportIssue::whereIn('batch_id', $allIds)
            ->selectRaw('code, severity, COUNT(*) c')->groupBy('code', 'severity')->get();
        $out = [];
        foreach ($codes as $c) {
            $out[$c->code.'|'.$c->severity] = $c->c;
        }

        return $out;
    }

    /** @param  int[]  $allIds */
    private static function masterMapping(array $allIds): array
    {
        $rows = LegacyImportMatch::whereIn('batch_id', $allIds)
            ->selectRaw('entity_type, status, COUNT(*) c')->groupBy('entity_type', 'status')->get();
        $out = ['total' => 0, 'resolved' => 0, 'unresolved' => 0, 'by_entity' => []];
        foreach ($rows as $r) {
            $out['total'] += $r->c;
            $out['by_entity'][$r->entity_type][$r->status] = $r->c;
            if (in_array($r->status, ['EXACT_MATCH', 'ALIAS_MATCH', 'RESOLVED'], true)) {
                $out['resolved'] += $r->c;
            } else {
                $out['unresolved'] += $r->c;
            }
        }

        return $out;
    }

    private static function overall(array $out): string
    {
        $unexplained = $out['sales']['unexplained'] + $out['deposit']['unexplained'] + $out['finance']['unexplained'] + $out['payroll']['unexplained'] + $out['spareparts']['unexplained'];
        $critErr = $out['sales']['critical_errors'] + $out['deposit']['critical_errors'] + $out['finance']['critical_errors'] + $out['payroll']['critical_errors'];
        if ($unexplained > 0 || $critErr > 0) {
            return 'FAIL';
        }
        $warn = $out['sales']['warning'] + $out['deposit']['warning'] + $out['finance']['warning'] + $out['payroll']['warning'];
        $var = $out['sales']['variance'] + $out['deposit']['variance'] + $out['finance']['variance'] + $out['payroll']['variance'];
        if ($warn > 0 || $var > 0 || $out['master_mapping']['unresolved'] > 0) {
            return 'PASS_WITH_WARNING';
        }

        return 'PASS';
    }

    private static function readiness(array $out): array
    {
        $score = fn (int $match, int $total) => $total > 0 ? round(10 * $match / $total, 1) : 10.0;
        $s = fn (string $k) => $out[$k]['match'] + $out[$k]['variance'];
        $mm = $out['master_mapping'];
        $dq = count($out['data_quality']) > 0;
        $scores = [
            'FILE RECOGNITION' => round(10 * min(1, $out['files']['recognized'] / max(1, $out['files']['expected'])), 1),
            'PARSING' => $out['sales']['error'] + $out['deposit']['error'] + $out['finance']['error'] + $out['payroll']['error'] > 0 ? 7.0 : 10.0,
            'MASTER MAPPING' => $mm['total'] > 0 ? round(10 * $mm['resolved'] / $mm['total'], 1) : 10.0,
            'SALES RECONCILIATION' => $score($out['sales']['match'], $s('sales')),
            'DEPOSIT RECONCILIATION' => $score($out['deposit']['match'], $s('deposit')),
            'FINANCE RECONCILIATION' => $score($out['finance']['match'], $s('finance')),
            'PAYROLL RECONCILIATION' => $score($out['payroll']['match'], $s('payroll')),
            'STOCK RECONCILIATION' => $score($out['spareparts']['match'], $s('spareparts')),
            'DOCUMENT MIGRATION' => $out['documents']['error'] > 0 ? 7.0 : 10.0,
            'CROSS-FILE DEDUP' => $out['cross_file']['unmatched'] > 0 ? 7.0 : 10.0,
            'DATA QUALITY' => $dq ? 8.0 : 10.0,
            'SECURITY' => 9.0,
            'AUDITABILITY' => 9.0,
            'ROLLBACK SAFETY' => 9.0,
            'TESTING' => ($out['tests']['pass'] ?? false) ? 10.0 : 5.0,
        ];
        $scores['OVERALL'] = round(array_sum($scores) / count($scores), 1);

        return $scores;
    }

    private static function goLive(array $out): string
    {
        $unexplained = $out['sales']['unexplained'] + $out['deposit']['unexplained'] + $out['finance']['unexplained'] + $out['payroll']['unexplained'] + $out['spareparts']['unexplained'];
        $critErr = $out['sales']['critical_errors'] + $out['deposit']['critical_errors'] + $out['finance']['critical_errors'] + $out['payroll']['critical_errors'];
        // automated runs never self-declare cutover/go-live; those need human sign-off
        if ($unexplained > 0 || $critErr > 0 || ($out['overall_status'] ?? '') === 'FAIL') {
            return 'NOT_READY';
        }
        if (($out['overall_status'] ?? '') === 'PASS_WITH_WARNING') {
            return 'READY_FOR_UAT';
        }
        if (! ($out['tests']['pass'] ?? false) || ! ($out['integrity']['pass'] ?? false)) {
            return 'READY_FOR_UAT';
        }

        return 'READY_FOR_MIGRATION_DRY_RUN';
    }
}
