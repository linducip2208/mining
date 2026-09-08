<?php

namespace App\Services\Bfj;

use App\Models\LegacyImportBatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyReconciliation;
use Illuminate\Support\Collection;

/**
 * Reconciliation benchmarks (§15-§16, §21-§22, §29-§30, §38, §42, §60-§62, §76-§83).
 * Compares staged detail totals vs legacy summary sheets; never mutates source.
 */
final class BfjReconciler
{
    /** @return array<int,array<string,mixed>> */
    public static function reconcile(LegacyImportBatch $batch): array
    {
        // idempotent re-run: replace previous entries for this batch
        LegacyReconciliation::where('batch_id', $batch->id)->delete();
        $results = [];
        $results = array_merge($results, self::sales($batch));
        $results = array_merge($results, self::deposit($batch));
        $results = array_merge($results, self::finance($batch));
        $results = array_merge($results, self::payroll($batch));
        $results = array_merge($results, self::stock($batch));

        foreach ($results as $i => $r) {
            $status = abs(($r['erp_total'] ?? 0) - ($r['legacy_total'] ?? 0)) < 0.01 ? 'MATCH' : 'VARIANCE';
            $results[$i]['status'] = $status;
            $results[$i]['variance'] = ($r['erp_total'] ?? 0) - ($r['legacy_total'] ?? 0);
            LegacyReconciliation::create([
                'batch_id' => $batch->id,
                'scope' => $r['scope'],
                'dimension' => $r['dimension'],
                'legacy_total' => $r['legacy_total'],
                'erp_total' => $r['erp_total'],
                'variance' => ($r['erp_total'] ?? 0) - ($r['legacy_total'] ?? 0),
                'status' => $status,
                'root_cause' => $r['root_cause'] ?? null,
            ]);
        }
        $batch->update([
            'status' => 'RECONCILED',
            'reconciliation' => ['entries' => count($results), 'run_at' => now()->toDateTimeString()],
        ]);

        return $results;
    }

    /** @return array<int,array<string,mixed>> */
    private static function sales(LegacyImportBatch $batch): array
    {
        $out = [];
        $detail = self::rows($batch, 'SALES');
        if ($detail->isEmpty()) {
            return $out;
        }
        $byDateMat = [];
        $byDate = [];
        $byMat = [];
        $byCust = [];
        $byChannel = [];
        $statusCount = [];
        foreach ($detail as $r) {
            $n = $r->normalized ?? [];
            $vol = (float) ($n['volume_m3'] ?? 0);
            $gross = (float) ($n['gross_amount'] ?? 0);
            $byDateMat[($n['transaction_date'] ?? '?').'|'.($n['material'] ?? '?')] = ($byDateMat[($n['transaction_date'] ?? '?').'|'.($n['material'] ?? '?')] ?? 0) + $vol;
            $byDate[$n['transaction_date'] ?? '?'] = ($byDate[$n['transaction_date'] ?? '?'] ?? 0) + $vol;
            $byMat[$n['material'] ?? '?'] = ($byMat[$n['material'] ?? '?'] ?? 0) + $vol;
            $byCust[$n['customer'] ?? '?'] = ($byCust[$n['customer'] ?? '?'] ?? 0) + $gross;
            $byChannel[$n['payment_channel'] ?? '?'] = ($byChannel[$n['payment_channel'] ?? '?'] ?? 0) + $gross;
            $statusCount[$n['amount_status'] ?? '?'] = ($statusCount[$n['amount_status'] ?? '?'] ?? 0) + 1;
        }
        foreach (self::rows($batch, ['SALES_RECAP_MATRIX', 'SALES_RETAIL_MATRIX']) as $s) {
            $n = $s->normalized ?? [];
            $dim = $n['dimension'] ?? '?';
            $legacy = (float) ($n['volume_total'] ?? 0);
            $parts = explode('|', $dim);
            $erp = match (count($parts)) {
                3 => self::erpFor($byDateMat, $parts[0].'|'.$parts[2], $byDate, $parts[0]),
                2 => $byDateMat[$dim] ?? $byDate[$parts[0]] ?? $byMat[$parts[1]] ?? 0,
                default => array_sum($byDate),
            };
            $out[] = ['scope' => 'SALES', 'dimension' => "VOLUME:{$dim}", 'legacy_total' => $legacy, 'erp_total' => round($erp, 4),
                'root_cause' => abs($erp - $legacy) < 0.01 ? null : 'Two recap scopes exist (REKAP BFJ static vs REKAP MAA formula); compared against full detail — scope-filtered review in Master Mapping'];
        }
        if ($out === []) {
            $out[] = ['scope' => 'SALES', 'dimension' => 'TOTAL_VOLUME', 'legacy_total' => 0, 'erp_total' => round(array_sum($byDate), 4), 'root_cause' => 'No recap sheet — detail total only'];
        }
        foreach ($byChannel as $ch => $tot) {
            $out[] = ['scope' => 'SALES', 'dimension' => "CHANNEL:{$ch}", 'legacy_total' => round($tot, 2), 'erp_total' => round($tot, 2), 'root_cause' => 'Detail channel total (clearing stays receivable until settlement)'];
        }
        foreach ($statusCount as $st => $c) {
            $out[] = ['scope' => 'SALES', 'dimension' => "FORMULA:{$st}", 'legacy_total' => $c, 'erp_total' => $c, 'root_cause' => $st === 'MATCH' ? null : 'Source formula/parse variance — value kept, flagged'];
        }

        return $out;
    }

    private static function erpFor(array $map, string $key, array $byDate, string $date): float
    {
        if (isset($map[$key])) {
            return $map[$key];
        }

        return $byDate[$date] ?? 0;
    }

    /** @return array<int,array<string,mixed>> */
    private static function deposit(LegacyImportBatch $batch): array
    {
        $out = [];
        $rows = self::rows($batch, 'CUSTOMER_DEPOSIT');
        if ($rows->isEmpty() && self::rows($batch, 'DEPOSIT_SISA')->isEmpty()) {
            return $out;
        }
        $bal = [];
        $vol = [];
        foreach ($rows as $r) {
            $n = $r->normalized ?? [];
            $k = $n['customer'] ?? '?';
            $amt = (float) ($n['amount'] ?? 0);
            $bal[$k] = ($bal[$k] ?? 0) + (in_array($n['type'] ?? '', ['CONSUMPTION', 'REFUND'], true) ? -$amt : $amt);
            if (! empty($n['volume'])) {
                $vol[$k.'|'.($n['material'] ?? '?')] = ($vol[$k.'|'.($n['material'] ?? '?')] ?? 0) + (($n['type'] ?? '') === 'CONSUMPTION' ? -(float) $n['volume'] : (float) $n['volume']);
            }
        }
        $sisa = [];
        foreach (self::rows($batch, 'DEPOSIT_SISA') as $s) {
            $n = $s->normalized ?? [];
            if (! empty($n['is_hutang'])) {
                $out[] = ['scope' => 'DEPOSIT', 'dimension' => 'HUTANG:'.($n['customer_legacy'] ?? '?'), 'legacy_total' => (float) ($n['legacy_balance'] ?? 0), 'erp_total' => (float) ($n['legacy_balance'] ?? 0), 'root_cause' => 'BFJ liability (AP-like), excluded from customer deposit balance'];

                continue;
            }
            // SISA labels map to delivery-sheet customers via explicit legacy aliases
            $key = BfjNormalizer::sisaSheet((string) ($n['customer_legacy'] ?? $n['customer'] ?? '?'));
            $sisa[$key] = ($sisa[$key] ?? 0) + (float) ($n['legacy_balance'] ?? 0);
        }
        foreach ($bal as $cust => $erpBal) {
            if (array_key_exists($cust, $sisa)) {
                $out[] = ['scope' => 'DEPOSIT', 'dimension' => "BALANCE:{$cust}", 'legacy_total' => $sisa[$cust], 'erp_total' => round($erpBal, 2),
                    'root_cause' => abs($erpBal - $sisa[$cust]) < 1 ? null : 'Staged detail does not foot to SISA — opening/deposit rows may be missing'];
            } else {
                $out[] = ['scope' => 'DEPOSIT', 'dimension' => "BALANCE:{$cust}", 'legacy_total' => 0, 'erp_total' => round($erpBal, 2), 'root_cause' => 'No SISA benchmark for this customer'];
            }
        }
        foreach ($sisa as $cust => $legacyBal) {
            if (! array_key_exists($cust, $bal)) {
                $out[] = ['scope' => 'DEPOSIT', 'dimension' => "BALANCE:{$cust}", 'legacy_total' => $legacyBal, 'erp_total' => 0, 'root_cause' => 'SISA without staged detail — customer sheet missing or not yet scanned'];
            }
        }
        foreach ($vol as $k => $v) {
            $out[] = ['scope' => 'DEPOSIT', 'dimension' => "VOLUME:{$k}", 'legacy_total' => round($v, 4), 'erp_total' => round($v, 4), 'root_cause' => 'Material-credit entitlement (volume), kept separate from money balance'];
        }
        // SMJ monthly table sisa vs SISA benchmark
        foreach (self::rows($batch, 'CUSTOMER_DEPOSIT') as $r) {
            $n = $r->normalized ?? [];
            if (! isset($n['month']) || ! isset($n['sisa']) || $n['sisa'] === null) {
                continue;
            }
            $out[] = ['scope' => 'DEPOSIT', 'dimension' => 'SMJ_SISA|'.$n['month'], 'legacy_total' => round((float) $n['sisa'], 2), 'erp_total' => round((float) $n['sisa'], 2), 'root_cause' => 'Monthly-table running sisa (informational; SISA sheet is the control)'];
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function finance(LegacyImportBatch $batch): array
    {
        $out = [];
        $detailSheets = $batch->sheets()->whereIn('detected_type', ['FINANCE_DETAIL', 'FINANCE'])->pluck('id');
        $detail = LegacyImportRow::whereIn('sheet_id', $detailSheets)->get();
        // cross-sheet duplicates: ARUS KAS and Laporan Rekap carry the same transactions
        $seen = [];
        $dupCount = 0;
        $perSheet = [];
        $byFlow = [];
        $opening = 0;
        $tableTotals = [];
        foreach ($detail as $r) {
            $n = $r->normalized ?? [];
            if (($n['benchmark_type'] ?? '') === 'TABLE_TOTAL') {
                $tableTotals[$r->sheet_id][] = (float) ($n['amount'] ?? 0);

                continue;
            }
            if (($n['benchmark_type'] ?? '') === 'BLOCK') {
                continue; // handled below
            }
            $flow = $n['flow_type'] ?? 'OPERATING';
            $in = (float) ($n['inflow'] ?? 0);
            $outAmt = (float) ($n['outflow'] ?? 0);
            // per-sheet footing happens before dedup: each sheet must foot to its own table total
            if ($flow !== 'TRANSFER_INTERNAL' && $flow !== 'OPENING_BALANCE') {
                $perSheet[$r->sheet_id] = ($perSheet[$r->sheet_id] ?? 0) + $outAmt;
            }
            $key = ($n['date'] ?? '').'|'.($n['description'] ?? '').'|'.$in.'|'.$outAmt;
            if (isset($seen[$key])) {
                $dupCount++;

                continue;
            }
            $seen[$key] = true;
            if ($flow === 'TRANSFER_INTERNAL') {
                $byFlow[$flow] = ($byFlow[$flow] ?? 0) + abs($in !== 0.0 ? $in : $outAmt);

                continue;
            }
            if ($flow === 'OPENING_BALANCE') {
                $opening += $in - $outAmt;

                continue;
            }
            $byFlow[$flow.'_IN'] = ($byFlow[$flow.'_IN'] ?? 0) + $in;
            $byFlow[$flow.'_OUT'] = ($byFlow[$flow.'_OUT'] ?? 0) + $outAmt;
        }
        $sheetNames = $batch->sheets()->whereIn('id', array_keys($perSheet))->pluck('sheet_name', 'id');
        foreach ($perSheet as $sid => $outSum) {
            $tt = $tableTotals[$sid][0] ?? null;
            $out[] = ['scope' => 'FINANCE', 'dimension' => 'SHEET_OUT:'.($sheetNames[$sid] ?? $sid), 'legacy_total' => $tt === null ? round($outSum, 2) : round($tt, 2), 'erp_total' => round($outSum, 2),
                'root_cause' => $tt === null ? 'No table total on this sheet — detail sum only' : (abs($tt - $outSum) < 1 ? null : 'Sheet detail outflow differs from its table SUM')];
        }
        if ($dupCount > 0) {
            $out[] = ['scope' => 'FINANCE', 'dimension' => 'DEDUP:CROSS_SHEET', 'legacy_total' => $dupCount, 'erp_total' => $dupCount, 'root_cause' => 'Identical (date|desc|amount) rows across detail sheets counted once'];
        }
        foreach ($byFlow as $k => $v) {
            $out[] = ['scope' => 'FINANCE', 'dimension' => "FLOW:{$k}", 'legacy_total' => round($v, 2), 'erp_total' => round($v, 2), 'root_cause' => str_starts_with($k, 'TRANSFER') ? 'Internal transfer — excluded from income/expense' : 'Deduped detail total'];
        }
        // account-block opening/inflow/closing benchmarks (ARUS KAS header block)
        $blkOpen = $blkIn = $blkClose = 0;
        $hasBlk = false;
        foreach ($detail as $r) {
            $n = $r->normalized ?? [];
            if (($n['benchmark_type'] ?? '') !== 'BLOCK') {
                continue;
            }
            $hasBlk = true;
            $label = mb_strtoupper($n['label'] ?? '');
            $amt = (float) ($n['amount'] ?? 0);
            if (str_contains($label, 'JULI') || str_contains($label, 'AWAL') || str_contains($label, 'SEBELUM')) {
                $blkOpen += $amt;
            } elseif (str_contains($label, 'MASUK')) {
                $blkIn += $amt;
            } elseif (str_contains($label, 'AGUSTUS') || str_contains($label, 'SEKARANG') || str_contains($label, 'AKHIR') || str_contains($label, 'SALDO')) {
                $blkClose = $amt;
            }
        }
        if ($hasBlk) {
            $in = array_sum(array_filter($byFlow, fn ($v, $k) => str_ends_with($k, '_IN'), ARRAY_FILTER_USE_BOTH));
            $outSum = array_sum(array_filter($byFlow, fn ($v, $k) => str_ends_with($k, '_OUT'), ARRAY_FILTER_USE_BOTH));
            $calc = $opening + $blkOpen + $blkIn + $in - $outSum;
            $out[] = ['scope' => 'FINANCE', 'dimension' => 'CASHFLOW:OPENING', 'legacy_total' => round($blkOpen + $opening, 2), 'erp_total' => round($blkOpen + $opening, 2), 'root_cause' => 'Block opening (+ detail openings)'];
            $out[] = ['scope' => 'FINANCE', 'dimension' => 'CASHFLOW:CLOSING', 'legacy_total' => round($blkClose, 2), 'erp_total' => round($calc, 2), 'root_cause' => abs($blkClose - $calc) < 1 ? null : 'OPENING+INFLOW−OUTFLOW ≠ legacy closing — investigate (inflows may live in sales/deposit workbooks)'];
        } elseif ($detail->isEmpty() && self::rows($batch, 'FINANCE_STATEMENT')->isEmpty()) {
            return $out;
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function payroll(LegacyImportBatch $batch): array
    {
        $out = [];
        $days = self::rows($batch, 'PAYROLL_DAY');
        if ($days->isEmpty()) {
            return $out;
        }
        $emp = [];
        foreach ($days as $r) {
            $n = $r->normalized ?? [];
            if (! isset($n['date'])) {
                continue; // recap-block benchmark rows handled below
            }
            $e = $n['employee'] ?? '?';
            $emp[$e]['days'] = ($emp[$e]['days'] ?? 0) + 1;
            $emp[$e]['normal_rec'] = ($emp[$e]['normal_rec'] ?? 0) + (float) ($n['normal_recorded'] ?? 0);
            $emp[$e]['normal_erp'] = ($emp[$e]['normal_erp'] ?? 0) + (float) ($n['normal_erp'] ?? 0);
            foreach ($n['overtime'] ?? [] as $t => $o) {
                $emp[$e]['ot_rec'][$t] = ($emp[$e]['ot_rec'][$t] ?? 0) + (float) ($o['recorded'] ?? 0);
                $emp[$e]['ot_erp'][$t] = ($emp[$e]['ot_erp'][$t] ?? 0) + (float) ($o['erp'] ?? 0);
            }
        }
        $block = [];
        foreach ($days as $r) {
            $n = $r->normalized ?? [];
            if (isset($n['date']) || ! isset($n['component'])) {
                continue;
            }
            $block[($n['employee'] ?? '?').'|'.$n['component']] = (float) ($n['legacy_value'] ?? 0);
        }
        $rekap = [];
        foreach (self::rows($batch, 'PAYROLL_RECAP') as $r) {
            $n = $r->normalized ?? [];
            if (isset($n['component'])) {
                // August block compares against day detail; July block is a separate arrears list
                $isAug = ! isset($n['block']) || str_contains(mb_strtoupper((string) $n['block']), 'AGUSTUS');
                if ($isAug) {
                    $rekap[($n['employee'] ?? '?').'|'.$n['component']] = (float) ($n['legacy_value'] ?? 0);
                }
            }
        }
        foreach ($emp as $e => $t) {
            $out[] = ['scope' => 'PAYROLL', 'dimension' => "HOURS:{$e}:NORMAL", 'legacy_total' => round($t['normal_rec'], 2), 'erp_total' => round($t['normal_erp'], 2),
                'root_cause' => abs($t['normal_rec'] - $t['normal_erp']) < 0.02 ? null : 'Normal-hours rule difference (shift/break) — review'];
            $otRec = round(array_sum($t['ot_rec'] ?? []), 2);
            $otErp = round(array_sum($t['ot_erp'] ?? []), 2);
            $out[] = ['scope' => 'PAYROLL', 'dimension' => "HOURS:{$e}:OVERTIME", 'legacy_total' => $otRec, 'erp_total' => $otErp,
                'root_cause' => abs($otRec - $otErp) < 0.02 ? null : 'Overtime rule difference — review start/end vs legacy Jml'];
            $cmp = [
                'TOTAL_HARI_KERJA' => ['JUMLAH_HARI_KERJA', $t['days']],
                'JUMLAH_JAM_LEMBUR' => [null, $otRec],
            ];
            foreach ($cmp as $blockComp => [$rekapComp, $dayVal]) {
                $legacy = $block[$e.'|'.$blockComp] ?? null;
                if ($legacy !== null) {
                    $out[] = ['scope' => 'PAYROLL', 'dimension' => "BLOCK:{$e}:{$blockComp}", 'legacy_total' => $legacy, 'erp_total' => round($dayVal, 2),
                        'root_cause' => abs($legacy - $dayVal) < 0.02 ? null : 'Day-detail sum differs from recap block'];
                }
                if ($rekapComp) {
                    $rv = $rekap[$e.'|'.$rekapComp] ?? null;
                    $bv = $block[$e.'|'.$blockComp] ?? null;
                    if ($rv !== null && $bv !== null) {
                        $out[] = ['scope' => 'PAYROLL', 'dimension' => "REKAP:{$e}:{$rekapComp}", 'legacy_total' => $bv, 'erp_total' => $rv,
                            'root_cause' => abs($bv - $rv) < 0.02 ? null : 'Recap block vs REKAP mismatch'];
                    }
                }
            }
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function stock(LegacyImportBatch $batch): array
    {
        $out = [];
        $moveIn = $moveOut = [];
        foreach (['SPAREPART_OPENING', 'SPAREPART_ISSUE'] as $type) {
            $rows = self::rows($batch, $type);
            if ($rows->isEmpty()) {
                continue;
            }
            $in = $outQty = 0;
            foreach ($rows as $r) {
                $n = $r->normalized ?? [];
                if (($n['movement'] ?? '') === 'OPENING_BALANCE' || ($n['qty'] ?? 0) > 0 && $type === 'SPAREPART_OPENING') {
                    $in += abs((float) ($n['qty'] ?? 0));
                    $moveIn[$n['code'] ?? '?'] = ($moveIn[$n['code'] ?? '?'] ?? 0) + abs((float) ($n['qty'] ?? 0));
                } else {
                    $outQty += abs((float) ($n['qty'] ?? 0));
                    $moveOut[$n['code'] ?? '?'] = ($moveOut[$n['code'] ?? '?'] ?? 0) + abs((float) ($n['qty'] ?? 0));
                }
            }
            $out[] = ['scope' => 'SPAREPART', 'dimension' => "{$type}:IN", 'legacy_total' => $in, 'erp_total' => $in, 'root_cause' => null];
            $out[] = ['scope' => 'SPAREPART', 'dimension' => "{$type}:OUT", 'legacy_total' => $outQty, 'erp_total' => $outQty, 'root_cause' => null];
        }
        // KARTU STOK: cumulative IN−OUT per item vs stated SALDO
        foreach (self::rows($batch, 'STOCK_CARD') as $r) {
            $n = $r->normalized ?? [];
            $code = $n['KODE'] ?? $n['code'] ?? '?';
            $masuk = self::num($n['STOK MASUK'] ?? null);
            $keluar = self::num($n['STOK KELUAR'] ?? null);
            $saldo = self::num($n['SALDO'] ?? $n['SALDO STOK'] ?? null);
            if ($masuk === null && $keluar === null && $saldo === null) {
                continue;
            }
            $moveIn[$code.'#card'] = ($moveIn[$code.'#card'] ?? 0) + ($masuk ?? 0);
            $moveOut[$code.'#card'] = ($moveOut[$code.'#card'] ?? 0) + ($keluar ?? 0);
            if ($saldo !== null) {
                $calc = ($moveIn[$code.'#card'] ?? 0) - ($moveOut[$code.'#card'] ?? 0);
                $out[] = ['scope' => 'SPAREPART', 'dimension' => "CARD:{$code}", 'legacy_total' => $saldo, 'erp_total' => $calc,
                    'root_cause' => abs($saldo - $calc) < 0.001 ? null : 'Card running balance differs from cumulative IN−OUT'];
            }
        }
        // STOCK OPNAME: arithmetic + system-vs-movement check
        foreach (self::rows($batch, 'STOCK_OPNAME') as $r) {
            $n = $r->normalized ?? [];
            $code = $n['KODE'] ?? '?';
            $sys = self::num($n['STOK SISTEM'] ?? null);
            $fis = self::num($n['STOK FISIK'] ?? null);
            $sel = self::num($n['SELISIH'] ?? null);
            if ($sys === null && $fis === null && $sel === null) {
                continue;
            }
            if ($sys !== null && $fis !== null && $sel !== null && abs(($fis - $sys) - $sel) > 0.001) {
                $out[] = ['scope' => 'SPAREPART', 'dimension' => "OPNAME_ARITH:{$code}", 'legacy_total' => $fis - $sys, 'erp_total' => $sel, 'root_cause' => 'FISIK−SISTEM ≠ SELISIH in source'];
            }
            if ($sys !== null) {
                $erpSys = ($moveIn[$code] ?? 0) - ($moveOut[$code] ?? 0);
                $out[] = ['scope' => 'SPAREPART', 'dimension' => "OPNAME:{$code}", 'legacy_total' => $sys, 'erp_total' => $erpSys,
                    'root_cause' => abs($sys - $erpSys) < 0.001 ? null : 'Legacy system stock differs from movement reconstruction'];
            }
        }
        // LAPORAN STOK: AWAL+MASUK−KELUAR=AKHIR per row
        foreach (self::rows($batch, 'STOCK_REPORT') as $r) {
            $n = $r->normalized ?? [];
            $code = $n['KODE'] ?? '?';
            $awal = self::num($n['STOK AWAL'] ?? null);
            $masuk = self::num($n['TOTAL MASUK'] ?? null);
            $keluar = self::num($n['TOTAL KELUAR'] ?? null);
            $akhir = self::num($n['STOK AKHIR'] ?? null);
            if ($awal === null && $masuk === null && $keluar === null && $akhir === null) {
                continue;
            }
            if ($awal !== null && $masuk !== null && $keluar !== null && $akhir !== null) {
                $calc = $awal + $masuk - $keluar;
                $out[] = ['scope' => 'SPAREPART', 'dimension' => "REPORT:{$code}", 'legacy_total' => $akhir, 'erp_total' => $calc,
                    'root_cause' => abs($akhir - $calc) < 0.001 ? null : 'AWAL+MASUK−KELUAR ≠ AKHIR in source'];
            } else {
                $out[] = ['scope' => 'SPAREPART', 'dimension' => "REPORT:{$code}", 'legacy_total' => $akhir ?? 0, 'erp_total' => $akhir ?? 0, 'root_cause' => 'Partial report row — informational'];
            }
        }

        return $out;
    }

    private static function num(mixed $v): ?float
    {
        if ($v === null || trim((string) $v) === '') {
            return null;
        }

        return is_numeric($v) ? (float) $v : null;
    }

    /** @return Collection<int,LegacyImportRow> */
    private static function rows(LegacyImportBatch $batch, string|array $types, ?bool $summaryOnly = null)
    {
        $sheetIds = $batch->sheets()->whereIn('detected_type', (array) $types)
            ->when($summaryOnly === true, fn ($q) => $q->where('is_summary', true))
            ->when($summaryOnly === false, fn ($q) => $q->where('is_summary', false))
            ->pluck('id');

        return LegacyImportRow::whereIn('sheet_id', $sheetIds)->get();
    }

    /** Cross-file duplicate guard: SALE vs FINANCE receipt (§80). */
    public static function crossFileHints(LegacyImportBatch $sales, LegacyImportBatch $finance): array
    {
        return [[
            'scope' => 'CROSS_FILE',
            'dimension' => "SALES#{$sales->id} ↔ FINANCE#{$finance->id}",
            'legacy_total' => 0,
            'erp_total' => 0,
            'root_cause' => 'Match sales payment vs finance cash receipt by date+amount+customer before posting revenue twice',
        ]];
    }
}
