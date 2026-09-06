<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Invoice;
use App\Models\MaintenanceCost;
use App\Models\MiningOtherCost;
use App\Models\OperatorIncentive;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\ProductionBatch;
use App\Models\TireMovement;
use App\Models\FuelIssue;

/**
 * Mining Cost Engine — computed live from real transactions.
 *
 * Labor (payroll gross, incl. overtime+incentive) is company-level and
 * allocated to sites proportional to saleable production in the period.
 * All other pools are site-direct. No hardcoded numbers.
 */
class CostEngine
{
    public static function compute(?int $companyId, ?int $siteId, string $from, string $to): array
    {
        // saleable tons per site (posted production net output)
        $prodQuery = ProductionBatch::where('status', 'POSTED')
            ->whereBetween('date', [$from, $to])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $siteTons = (clone $prodQuery)
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->selectRaw('site_id, COALESCE(SUM(net_output),0) tons')
            ->groupBy('site_id')
            ->pluck('tons', 'site_id');
        $totalTons = (float) $siteTons->sum();

        // labor pool (company level, allocated by production share)
        $laborTotal = (float) PayrollRun::whereIn('status', ['CALCULATED', 'APPROVED', 'POSTED', 'PAID'])
            ->where('period', '>=', substr($from, 0, 7))
            ->where('period', '<=', substr($to, 0, 7))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->sum('total_gross');
        $laborRuns = PayrollRun::whereIn('status', ['CALCULATED', 'APPROVED', 'POSTED', 'PAID'])
            ->where('period', '>=', substr($from, 0, 7))
            ->where('period', '<=', substr($to, 0, 7))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->pluck('id');

        $components = [];
        $components['fuel'] = [
            'label' => 'BBM',
            'amount' => round((float) FuelIssue::where('status', 'POSTED')
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
                ->whereBetween('issue_date', [$from, $to])->sum('total_cost'), 2),
            'route' => 'report.maintenance',
        ];
        $components['maintenance'] = [
            'label' => 'Pemeliharaan + Sparepart',
            'amount' => round((float) MaintenanceCost::whereHas('workOrder', function ($q) use ($companyId, $siteId, $from, $to) {
                $q->when($companyId, fn ($w) => $w->where('company_id', $companyId))
                    ->when($siteId, fn ($w) => $w->where('site_id', $siteId))
                    ->whereBetween('date', [$from, $to]);
            })->sum('amount'), 2),
            'route' => 'report.maintenance',
        ];
        $components['tire_repair'] = [
            'label' => 'Repair Ban',
            'amount' => round((float) TireMovement::where('movement_type', 'REPAIR')
                ->whereBetween('trx_date', [$from, $to])
                ->whereHas('tire', fn ($q) => $q->when($companyId, fn ($w) => $w->where('company_id', $companyId)))
                ->sum('cost'), 2),
            'route' => 'report.maintenance',
        ];

        // depreciation across site equipment
        $equipIds = Equipment::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->pluck('id');
        $depr = 0;
        foreach ($equipIds as $eid) {
            $depr += FleetService::depreciationForPeriod($eid, $from, $to);
        }
        $components['depreciation'] = ['label' => 'Penyusutan Alat', 'amount' => round($depr, 2), 'route' => 'report.maintenance'];

        // manual components
        $manual = MiningOtherCost::where('status', 'POSTED')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->where('period', '>=', substr($from, 0, 7))
            ->where('period', '<=', substr($to, 0, 7))
            ->selectRaw('component, COALESCE(SUM(amount),0) amount')
            ->groupBy('component')
            ->pluck('amount', 'component');
        $manualLabels = ['CONTRACTOR' => 'Kontraktor', 'ROYALTY' => 'Royalti', 'OVERHEAD' => 'Overhead', 'HAULING' => 'Hauling', 'CRUSHER' => 'Crusher', 'OTHER' => 'Lainnya'];
        foreach ($manualLabels as $code => $label) {
            $components['manual_' . strtolower($code)] = [
                'label' => $label . ' (manual)',
                'amount' => round((float) ($manual[$code] ?? 0), 2),
                'route' => 'report.maintenance',
            ];
        }

        // labor allocation by production share
        $share = ($totalTons > 0 && $siteId) ? ((float) ($siteTons[$siteId] ?? 0) / $totalTons) : 1.0;
        if (!$siteId) {
            $share = 1.0;
        }
        $components['labor'] = [
            'label' => 'Tenaga Kerja (gaji+lembur+insentif)',
            'amount' => round($laborTotal * $share, 2),
            'route' => 'report.hr',
            'note' => $siteId ? 'Alokasi proporsional produksi site (' . round($share * 100, 1) . '%)' : 'Total perusahaan',
        ];
        $components['incentive_info'] = [
            'label' => '— dari itu: Insentif Operator',
            'amount' => round((float) OperatorIncentive::whereIn('status', ['APPROVED', 'INCLUDED_IN_PAYROLL'])
                ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
                ->where('period', '>=', substr($from, 0, 7))
                ->where('period', '<=', substr($to, 0, 7))
                ->sum('amount'), 2),
            'route' => 'report.hr',
            'info_only' => true,
        ];

        $directTotal = collect($components)->reject(fn ($c) => $c['info_only'] ?? false)->sum('amount');
        $saleable = $siteId ? (float) ($siteTons[$siteId] ?? 0) : $totalTons;

        // revenue per site via invoice -> SO site
        $revenue = (float) Invoice::whereIn('invoices.status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->join('sales_orders', 'sales_orders.id', '=', 'invoices.sales_order_id')
            ->when($companyId, fn ($q) => $q->where('invoices.company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('sales_orders.site_id', $siteId))
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->sum('invoices.total');

        $costPerTon = $saleable > 0 ? round($directTotal / $saleable, 2) : 0;
        $revPerTon = $saleable > 0 ? round($revenue / $saleable, 2) : 0;

        return [
            'from' => $from,
            'to' => $to,
            'saleable_ton' => round($saleable, 2),
            'components' => $components,
            'total_cost' => round($directTotal, 2),
            'revenue' => round($revenue, 2),
            'cost_per_ton' => $costPerTon,
            'revenue_per_ton' => $revPerTon,
            'margin_per_ton' => round($revPerTon - $costPerTon, 2),
            'margin_total' => round($revenue - $directTotal, 2),
        ];
    }

    public static function postOtherCost(MiningOtherCost $cost): void
    {
        \DB::transaction(function () use ($cost) {
            if ($cost->status === 'POSTED') {
                throw new \DomainException('Biaya sudah diposting.');
            }
            if ($cost->status !== 'APPROVED') {
                throw new \DomainException('Biaya harus APPROVED sebelum posting.');
            }
            $map = [
                'CONTRACTOR' => 'ADMIN_EXPENSE',
                'ROYALTY' => 'TAX_EXPENSE',
                'OVERHEAD' => 'ADMIN_EXPENSE',
                'HAULING' => 'FUEL_EXPENSE',
                'CRUSHER' => 'MAINTENANCE_EXPENSE',
                'OTHER' => 'ADMIN_EXPENSE',
            ];
            $journal = AccountingService::post($cost->company_id, now()->toDateString(), [
                ['code' => AccountingService::map($map[$cost->component] ?? 'ADMIN_EXPENSE'), 'debit' => (float) $cost->amount, 'memo' => $cost->component . ' ' . $cost->period, 'site_id' => $cost->site_id],
                ['code' => AccountingService::map('CASH_MAIN'), 'credit' => (float) $cost->amount, 'memo' => $cost->component . ' ' . $cost->period],
            ], 'OTHER_COST', $cost->id, 'COST-' . $cost->id, 'Biaya tambang ' . $cost->component, 'COST');
            $cost->update(['status' => 'POSTED', 'journal_entry_id' => $journal->id]);
            AuditService::log('POST', 'COST', $cost->id, MiningOtherCost::class, null, ['amount' => $cost->amount]);
        });
    }
}
