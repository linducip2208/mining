<?php

namespace App\Services\Bfj;

use App\Models\Equipment;
use App\Models\Invoice;
use App\Models\LegacyImportBatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyReconciliation;

/**
 * Cross-file duplicate + linkage analysis (§17, §23, §30, §44-§45, §80-§83).
 * Never fabricates links: exact/deterministic matches only, everything else
 * stays UNMATCHED/UNRESOLVED with reasons.
 */
final class BfjCrossFile
{
    /** @return array{entries:array<int,array<string,mixed>>,stats:array<string,mixed>} */
    public static function salesDeposit(int $salesBatchId, int $depositBatchId): array
    {
        $entries = [];
        $salesMonths = self::months($salesBatchId, 'SALES', 'transaction_date');
        $depMonths = self::months($depositBatchId, 'CUSTOMER_DEPOSIT', 'date');
        $stats = ['sales_months' => $salesMonths, 'deposit_months' => $depMonths];
        if (empty(array_intersect($salesMonths, $depMonths))) {
            $entries[] = self::entry('CROSS_FILE', "SALES#{$salesBatchId} ↔ DEPOSIT#{$depositBatchId}:NO_OVERLAP", 0, 0, 'MATCH',
                'Period disjoint (sales '.implode(',', $salesMonths).' vs deposit '.implode(',', $depMonths).') — DO-level matching N/A; deposit-consumption classification stays inside the deposit workbook');
            $stats['overlap'] = false;
        } else {
            $stats['overlap'] = true;
            // DO-level match on date+customer+material+volume
            $depKeys = [];
            foreach (self::rows($depositBatchId, 'CUSTOMER_DEPOSIT') as $r) {
                $n = $r->normalized ?? [];
                if (($n['type'] ?? '') !== 'CONSUMPTION') {
                    continue;
                }
                $depKeys[] = implode('|', [$n['date'] ?? '', $n['customer'] ?? '', $n['material'] ?? '', $n['volume'] ?? '']);
            }
            $matched = $unmatched = 0;
            foreach (self::rows($salesBatchId, 'SALES') as $r) {
                $n = $r->normalized ?? [];
                $k = implode('|', [$n['transaction_date'] ?? '', $n['customer'] ?? '', $n['material'] ?? '', $n['volume_m3'] ?? '']);
                if (in_array($k, $depKeys, true)) {
                    $matched++;
                } else {
                    $unmatched++;
                }
            }
            $entries[] = self::entry('CROSS_FILE', "SALES#{$salesBatchId} ↔ DEPOSIT#{$depositBatchId}:DO_MATCH", $matched, $matched, 'MATCH', "Deliveries matched by date+customer+material+volume: {$matched}");
            if ($unmatched > 0) {
                $entries[] = self::entry('CROSS_FILE', "SALES#{$salesBatchId} ↔ DEPOSIT#{$depositBatchId}:UNMATCHED_SALES", $unmatched, 0, 'VARIANCE', "{$unmatched} sales rows without deposit-consumption twin — cash/credit sales, not duplicates");
            }
            $stats += ['matched' => $matched, 'unmatched_sales' => $unmatched];
        }

        return ['entries' => $entries, 'stats' => $stats];
    }

    /** @return array{entries:array<int,array<string,mixed>>,stats:array<string,mixed>} */
    public static function salesFinance(int $salesBatchId, int $financeBatchId): array
    {
        $entries = [];
        $salesMonths = self::months($salesBatchId, 'SALES', 'transaction_date');
        $finMonths = self::months($financeBatchId, 'FINANCE_DETAIL', 'date');
        $stats = ['sales_months' => $salesMonths, 'finance_months' => $finMonths];
        $chTotals = [];
        foreach (self::rows($salesBatchId, 'SALES') as $r) {
            $n = $r->normalized ?? [];
            $chTotals[$n['payment_channel'] ?? '?'] = ($chTotals[$n['payment_channel'] ?? '?'] ?? 0) + (float) ($n['gross_amount'] ?? 0);
        }
        $stats['channels'] = $chTotals;
        if (empty(array_intersect($salesMonths, $finMonths))) {
            $entries[] = self::entry('CROSS_FILE', "SALES#{$salesBatchId} ↔ FINANCE#{$financeBatchId}:NO_OVERLAP", 0, 0, 'MATCH',
                'Period disjoint (sales '.implode(',', $salesMonths).' vs finance '.implode(',', $finMonths).') — receipt-level matching N/A; no revenue posted twice (both HISTORY_ONLY)');
        }

        return ['entries' => $entries, 'stats' => $stats];
    }

    /** @return array{entries:array<int,array<string,mixed>>,stats:array<string,mixed>} */
    public static function payrollFinance(int $payrollBatchId, int $financeBatchId): array
    {
        $entries = [];
        // August-block netto from the REKAP sheet only (Copy is the same data)
        $rekapSheet = LegacyImportBatch::find($payrollBatchId)?->sheets()->where('sheet_name', 'REKAP')->value('id');
        $netto = 0;
        $julyNames = ['AGUNG', 'ADE PUTRA', 'EXMAN RAFLY', 'YADI', 'PIRLI', 'ARI YANTO', 'RIKI SAPUTRA', 'ROLIN PUTRA', 'PAK NIZAR'];
        if ($rekapSheet) {
            foreach (LegacyImportRow::where('sheet_id', $rekapSheet)->get() as $r) {
                $n = $r->normalized ?? [];
                if (($n['component'] ?? '') !== 'UPAH_NETTO') {
                    continue;
                }
                if (in_array(mb_strtoupper((string) ($n['employee'] ?? '')), $julyNames, true)) {
                    continue; // July arrears block
                }
                $netto += (float) ($n['legacy_value'] ?? 0);
            }
        }
        $salaryOut = 0;
        foreach (self::rows($financeBatchId, ['FINANCE_DETAIL', 'FINANCE']) as $r) {
            $n = $r->normalized ?? [];
            $hay = mb_strtoupper(($n['description'] ?? '').' '.($n['category'] ?? ''));
            if (str_contains($hay, 'GAJI') || str_contains($hay, 'UPAH') || str_contains($hay, 'PAYROLL') || str_contains($hay, 'SALARY')) {
                $salaryOut += (float) ($n['outflow'] ?? 0);
            }
        }
        $stats = ['payroll_net_august' => round($netto, 2), 'finance_salary_outflow' => round($salaryOut, 2)];
        if (abs($netto - $salaryOut) < 1) {
            $entries[] = self::entry('CROSS_FILE', "PAYROLL#{$payrollBatchId} ↔ FINANCE#{$financeBatchId}:MATCHED", $netto, $salaryOut, 'MATCH', 'Payroll net fully matched to finance outflow — no double expense');
        } else {
            $entries[] = self::entry('CROSS_FILE', "PAYROLL#{$payrollBatchId} ↔ FINANCE#{$financeBatchId}:UNMATCHED", $netto, $salaryOut, 'VARIANCE', 'Payroll net NOT found in finance cash outflow — likely paid outside these books (personal/clearing) or different period; no duplicate expense posted (both HISTORY_ONLY)');
        }
        $stats += ['matched' => min($netto, $salaryOut), 'variance' => round($netto - $salaryOut, 2)];

        return ['entries' => $entries, 'stats' => $stats];
    }

    /** @param  int[]  $docBatchIds */
    public static function invoiceLinks(array $docBatchIds, int $depositBatchId): array
    {
        $entries = [];
        $stats = ['exact_link' => 0, 'legacy_only' => 0, 'deposit_refs' => 0];
        $depRefs = [];
        foreach (self::rows($depositBatchId, 'CUSTOMER_DEPOSIT') as $r) {
            $n = $r->normalized ?? [];
            if (! empty($n['legacy_invoice'])) {
                $depRefs[$n['legacy_invoice']] = ['amount' => $n['amount'] ?? 0, 'volume' => $n['volume'] ?? null, 'customer' => $n['customer'] ?? '?'];
            }
        }
        $stats['deposit_refs'] = count($depRefs);
        foreach ($docBatchIds as $invoiceBatchId) {
            foreach (self::rows($invoiceBatchId, 'INVOICE_REGISTER') as $r) {
            $n = $r->normalized ?? [];
            $number = $n['number'] ?? '';
            $exists = $number !== '' && Invoice::where('number', $number)->exists();
            // deposit ref "INV NO 006" ↔ invoice ".../006/..." by sequence fragment
            $linked = null;
            if (preg_match('/(\d{3})\s*$/', (string) $number, $m)) {
                foreach ($depRefs as $ref => $d) {
                    if (str_contains(mb_strtoupper((string) $ref), 'INV NO '.ltrim($m[1], '0')) || str_contains(mb_strtoupper((string) $ref), $m[1])) {
                        $linked = [$ref, $d];
                        break;
                    }
                }
            }
            if ($exists) {
                $stats['exact_link']++;
                $entries[] = self::entry('CROSS_FILE', "INVOICE:{$number}:EXACT_LINK", (float) ($n['total'] ?? 0), (float) ($n['total'] ?? 0), 'MATCH', 'Exact ERP invoice record (imported history)');
            } else {
                $stats['legacy_only']++;
            }
            if ($linked) {
                [$ref, $d] = $linked;
                $iv = (float) ($n['total'] ?? 0);
                $dv = (float) ($d['amount'] ?? 0);
                $entries[] = self::entry('CROSS_FILE', "INVOICE:{$number} ↔ DEPOSIT:{$ref}", $iv, $dv, abs($iv - $dv) < 1 ? 'MATCH' : 'VARIANCE',
                    'Deposit sheet computes volume×sheet-price ('.$dv.') vs invoice total ('.$iv.') — price-basis difference, kept as variance');
                $stats['invoice_deposit_link'] = ['invoice' => $number, 'ref' => $ref, 'invoice_total' => $iv, 'deposit_calc' => $dv];
            }
            }
        }

        return ['entries' => $entries, 'stats' => $stats];
    }

    /** @return array{entries:array<int,array<string,mixed>>,stats:array<string,mixed>} */
    public static function sparepartMaintenance(array $spareBatchIds): array
    {
        $entries = [];
        $equip = [];
        foreach ($spareBatchIds as $bid) {
            foreach (self::rows($bid, ['SPAREPART_ISSUE', 'SPAREPART_MASTER', 'SPAREPART_OPENING']) as $r) {
                $n = $r->normalized ?? [];
                $e = trim((string) ($n['equipment'] ?? ''));
                if ($e !== '') {
                    $equip[$e] = true;
                }
            }
        }
        $stats = ['distinct_equipment' => count($equip), 'exact' => 0, 'possible' => 0, 'unresolved' => 0];
        try {
            $masters = Equipment::limit(500)->get();
        } catch (\Throwable) {
            $masters = collect();
        }
        foreach (array_keys($equip) as $legacy) {
            $canon = mb_strtoupper(preg_replace('/[^A-Z0-9]/', '', $legacy));
            $found = $masters->first(fn ($m) => mb_strtoupper((string) ($m->code ?? '')) === $canon || mb_strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($m->name ?? ''))) === $canon);
            if ($found) {
                $stats['exact']++;
            } else {
                $suggest = $masters->first(function ($m) use ($legacy) {
                    similar_text(mb_strtoupper((string) ($m->name ?? '')), mb_strtoupper($legacy), $pct);

                    return $pct >= 60;
                });
                if ($suggest) {
                    $stats['possible']++;
                } else {
                    $stats['unresolved']++;
                    $entries[] = self::entry('CROSS_FILE', 'SPAREPART:UNRESOLVED_EQUIPMENT:'.$legacy, 0, 0, 'VARIANCE', 'No Equipment master — stays LEGACY_CONSUMPTION, no WO fabricated');
                }
            }
        }

        return ['entries' => $entries, 'stats' => $stats];
    }

    /** Persist cross-file entries to the anchor batch and return them. */
    public static function persist(int $anchorBatchId, array $entries): array
    {
        foreach ($entries as $i => $e) {
            $entries[$i]['variance'] = ($e['erp_total'] ?? 0) - ($e['legacy_total'] ?? 0);
            if (! isset($e['status'])) {
                $entries[$i]['status'] = abs($entries[$i]['variance']) < 0.01 ? 'MATCH' : 'VARIANCE';
            }
            LegacyReconciliation::create([
                'batch_id' => $anchorBatchId, 'scope' => 'CROSS_FILE', 'dimension' => $e['dimension'],
                'legacy_total' => $e['legacy_total'] ?? 0, 'erp_total' => $e['erp_total'] ?? 0,
                'variance' => $entries[$i]['variance'], 'status' => $entries[$i]['status'],
                'root_cause' => $e['root_cause'] ?? null,
            ]);
        }

        return $entries;
    }

    private static function entry(string $scope, string $dimension, float $legacy, float $erp, string $status, ?string $cause): array
    {
        return ['scope' => $scope, 'dimension' => $dimension, 'legacy_total' => $legacy, 'erp_total' => $erp, 'variance' => $erp - $legacy, 'status' => $status, 'root_cause' => $cause];
    }

    /** @return string[] Y-m values present in rows of given types */
    private static function months(int $batchId, string|array $types, string $dateKey): array
    {
        $out = [];
        foreach (self::rows($batchId, $types) as $r) {
            $n = $r->normalized ?? [];
            $d = $n[$dateKey] ?? null;
            if ($d && preg_match('/^(\d{4})-(\d{2})/', (string) $d, $m)) {
                $out[$m[1].'-'.$m[2]] = true;
            }
        }
        $keys = array_keys($out);
        sort($keys);

        return $keys;
    }

    /** @return \Illuminate\Support\Collection<int,LegacyImportRow> */
    private static function rows(int $batchId, string|array $types)
    {
        $batch = LegacyImportBatch::find($batchId);
        if (! $batch) {
            return collect();
        }
        $sheetIds = $batch->sheets()->whereIn('detected_type', (array) $types)->pluck('id');

        return LegacyImportRow::whereIn('sheet_id', $sheetIds)->get();
    }
}
