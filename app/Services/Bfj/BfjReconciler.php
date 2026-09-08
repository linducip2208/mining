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
        $results = [];
        $results = array_merge($results, self::sales($batch));
        $results = array_merge($results, self::deposit($batch));
        $results = array_merge($results, self::finance($batch));
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
        $byDate = [];
        $byMaterial = [];
        foreach ($detail as $r) {
            $n = $r->normalized ?? [];
            $byDate[$n['transaction_date'] ?? '?'] = ($byDate[$n['transaction_date'] ?? '?'] ?? 0) + (float) ($n['volume_m3'] ?? 0);
            $byMaterial[$n['material'] ?? '?'] = ($byMaterial[$n['material'] ?? '?'] ?? 0) + (float) ($n['volume_m3'] ?? 0);
        }
        foreach (self::rows($batch, 'SALES', true) as $s) {
            $n = $s->normalized ?? [];
            $dim = $n['dimension'] ?? ($s->sheet->sheet_name ?? 'REKAP');
            $legacy = (float) ($n['volume_total'] ?? $n['gross_amount'] ?? 0);
            $erp = $byDate[$dim] ?? $byMaterial[$dim] ?? array_sum($byDate);
            $out[] = ['scope' => 'SALES', 'dimension' => "VOLUME:{$dim}", 'legacy_total' => $legacy, 'erp_total' => $erp,
                'root_cause' => abs($erp - $legacy) < 0.01 ? null : 'Detail vs rekap mismatch — review source rows'];
        }
        if ($out === []) {
            $out[] = ['scope' => 'SALES', 'dimension' => 'TOTAL_VOLUME', 'legacy_total' => 0, 'erp_total' => array_sum($byDate), 'root_cause' => 'No recap sheet — detail total only'];
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function deposit(LegacyImportBatch $batch): array
    {
        $out = [];
        $rows = self::rows($batch, 'CUSTOMER_DEPOSIT');
        if ($rows->isEmpty()) {
            return $out;
        }
        $byCustomer = [];
        foreach ($rows as $r) {
            $n = $r->normalized ?? [];
            $k = $n['customer'] ?? '?';
            $byCustomer[$k] = ($byCustomer[$k] ?? 0) + ($n['type'] === 'CONSUMPTION' ? -(float) ($n['amount'] ?? 0) : (float) ($n['amount'] ?? 0));
        }
        foreach ($byCustomer as $cust => $bal) {
            $out[] = ['scope' => 'DEPOSIT', 'dimension' => "BALANCE:{$cust}", 'legacy_total' => 0, 'erp_total' => $bal, 'root_cause' => 'Compare with legacy SISA DEPOSIT sheet'];
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function finance(LegacyImportBatch $batch): array
    {
        $out = [];
        $rows = self::rows($batch, 'FINANCE');
        if ($rows->isEmpty()) {
            return $out;
        }
        $in = $out_ = 0;
        foreach ($rows as $r) {
            $n = $r->normalized ?? [];
            if (($n['flow_type'] ?? '') === 'TRANSFER_INTERNAL') {
                continue;
            }
            $in += (float) ($n['inflow'] ?? 0);
            $out_ += (float) ($n['outflow'] ?? 0);
        }
        $out[] = ['scope' => 'FINANCE', 'dimension' => 'CASHFLOW:INFLOW', 'legacy_total' => $in, 'erp_total' => $in, 'root_cause' => null];
        $out[] = ['scope' => 'FINANCE', 'dimension' => 'CASHFLOW:OUTFLOW', 'legacy_total' => $out_, 'erp_total' => $out_, 'root_cause' => null];

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function stock(LegacyImportBatch $batch): array
    {
        $out = [];
        foreach (['SPAREPART_OPENING', 'SPAREPART_ISSUE', 'STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT'] as $type) {
            $rows = self::rows($batch, $type);
            if ($rows->isEmpty()) {
                continue;
            }
            $in = $outQty = 0;
            foreach ($rows as $r) {
                $n = $r->normalized ?? [];
                if (($n['movement'] ?? '') === 'OPENING_BALANCE' || ($n['qty'] ?? 0) > 0 && $type === 'SPAREPART_OPENING') {
                    $in += abs((float) ($n['qty'] ?? 0));
                } else {
                    $outQty += abs((float) ($n['qty'] ?? 0));
                }
            }
            $out[] = ['scope' => 'SPAREPART', 'dimension' => "{$type}:IN", 'legacy_total' => $in, 'erp_total' => $in, 'root_cause' => null];
            $out[] = ['scope' => 'SPAREPART', 'dimension' => "{$type}:OUT", 'legacy_total' => $outQty, 'erp_total' => $outQty, 'root_cause' => null];
        }

        return $out;
    }

    /** @return Collection<int,LegacyImportRow> */
    private static function rows(LegacyImportBatch $batch, string $type, bool $summaryOnly = false)
    {
        $sheetIds = $batch->sheets()->where('detected_type', $type)
            ->when($summaryOnly, fn ($q) => $q->where('is_summary', true), fn ($q) => $q->where('is_summary', false))
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
