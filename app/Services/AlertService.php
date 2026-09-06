<?php

namespace App\Services;

use App\Models\AlertRule;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\MaintenanceSchedule;
use App\Models\PriceVariance;
use App\Models\User;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Proactive alerting: scans system state and notifies users.
 * Triggered by scheduler (daily) or manually via artisan alert:scan.
 *
 * Channel-ready: in-app (database) sekarang; email/WhatsApp cukup ditambahkan
 * di SystemAlert::via() tanpa mengubah service ini.
 */
class AlertService
{
    public function run(): array
    {
        $summary = [];
        $recipients = $this->recipients();

        foreach ($this->checks() as $code => $check) {
            $rule = AlertRule::where('event_type', $code)->where('is_active', true)->first();
            if ($rule === null || !$check['enabled']) {
                continue;
            }
            $items = $check['detect']();
            if ($items->isEmpty()) {
                continue;
            }
            $summary[$code] = $items->count();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new SystemAlert($code, $check['title'], $items));
            }
        }

        return $summary;
    }

    protected function recipients()
    {
        return User::where('status', 'ACTIVE')
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN', 'SYSTEM_ADMIN']));
            })
            ->get();
    }

    protected function checks(): array
    {
        return [
            'MIN_STOCK' => [
                'title' => 'Stok mencapai batas minimum',
                'enabled' => true,
                'detect' => fn () => Item::query()
                    ->where('min_stock', '>', 0)
                    ->whereRaw('(SELECT COALESCE(SUM(qty_in - qty_out),0) FROM stock_ledger WHERE stock_ledger.item_id = items.id) < min_stock')
                    ->limit(50)->get(),
            ],
            'INVOICE_OVERDUE' => [
                'title' => 'Faktur melewati jatuh tempo',
                'enabled' => true,
                'detect' => fn () => Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
                    ->whereDate('due_date', '<', now())->limit(50)->get(),
            ],
            'DOCUMENT_EXPIRY' => [
                'title' => 'Dokumen/permit akan kadaluarsa dalam 30 hari',
                'enabled' => true,
                'detect' => fn () => Document::whereIn('category', ['LEGAL', 'PERMIT'])
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', now()->addDays(30))
                    ->whereDate('expiry_date', '>=', now())->limit(50)->get(),
            ],
            'MAINTENANCE_DUE' => [
                'title' => 'Jadwal pemeliharaan jatuh tempo',
                'enabled' => true,
                'detect' => fn () => MaintenanceSchedule::where('is_active', true)
                    ->whereNotNull('next_due')->whereDate('next_due', '<=', now()->addDays(7))->limit(50)->get(),
            ],
            'PRICE_VARIANCE' => [
                'title' => 'Selisih harga abnormal menunggu persetujuan',
                'enabled' => true,
                'detect' => fn () => PriceVariance::where('approval_status', 'PENDING')
                    ->where('variance_percentage', '>=', 10)->limit(50)->get(),
            ],
            'APPROVAL_PENDING' => [
                'title' => 'Permintaan persetujuan tertunda lebih dari 3 hari',
                'enabled' => true,
                'detect' => fn () => \App\Models\ApprovalRequest::where('status', 'PENDING')
                    ->whereDate('submitted_at', '<=', now()->subDays(3))->limit(50)->get(),
            ],
            'FUEL_ANOMALY' => [
                'title' => 'Anomali konsumsi BBM melewati threshold',
                'enabled' => true,
                'detect' => fn () => \App\Models\FuelIssue::where('status', 'POSTED')
                    ->whereIn('variance_status', ['WARNING', 'CRITICAL'])
                    ->whereDate('issue_date', '>=', now()->subDays(7))->limit(50)->get(),
            ],
            'STOCK_VARIANCE' => [
                'title' => 'Variansi survei stockpile butuh investigasi',
                'enabled' => true,
                'detect' => fn () => \App\Models\StockpileSurvey::where('status', 'INVESTIGATE')->limit(50)->get(),
            ],
            'EQUIPMENT_BREAKDOWN' => [
                'title' => 'Alat BREAKDOWN',
                'enabled' => true,
                'detect' => fn () => \App\Models\Equipment::where('status', 'BREAKDOWN')->limit(50)->get(),
            ],
            'MAINTENANCE_OVERDUE' => [
                'title' => 'Jadwal pemeliharaan terlewat',
                'enabled' => true,
                'detect' => fn () => MaintenanceSchedule::where('is_active', true)
                    ->whereNotNull('next_due')->whereDate('next_due', '<', now())->limit(50)->get(),
            ],
            'CONTRACT_EXPIRY' => [
                'title' => 'Kontrak kedaluwarsa dalam 30 hari',
                'enabled' => true,
                'detect' => fn () => \App\Models\CustomerContract::where('status', 'ACTIVE')
                    ->whereDate('end_date', '<=', now()->addDays(30))->limit(50)->get(),
            ],
            'BUDGET_EXCEEDED' => [
                'title' => 'Budget terpakai >= 90%',
                'enabled' => true,
                'detect' => function () {
                    $hits = collect();
                    foreach (\App\Models\Budget::whereIn('status', ['APPROVED', 'REVISED'])->with('lines.chartOfAccount')->limit(20)->get() as $budget) {
                        foreach ($budget->lines as $line) {
                            $r = \App\Services\BudgetService::lineReport($line);
                            if ($r['used_pct'] >= 90) {
                                $hits->push((object) ['id' => $line->id, 'number' => $budget->number . ' ' . ($line->chartOfAccount?->code ?? '') . ' ' . $r['used_pct'] . '%']);
                            }
                        }
                        if ($hits->count() >= 50) {
                            break;
                        }
                    }
                    return $hits;
                },
            ],
            'OVERDUE_AP' => [
                'title' => 'Hutang supplier jatuh tempo',
                'enabled' => true,
                'detect' => fn () => \App\Models\VendorBill::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
                    ->whereDate('due_date', '<', now())->limit(50)->get(),
            ],
            'QUALITY_FAILURE' => [
                'title' => 'Uji QC gagal / hold aktif',
                'enabled' => true,
                'detect' => fn () => \App\Models\QualityHold::where('status', 'HOLD')->limit(50)->get(),
            ],
            'HIGH_DOWNTIME' => [
                'title' => 'Downtime tinggi 7 hari terakhir',
                'enabled' => true,
                'detect' => fn () => \App\Models\WorkOrder::with('equipment')
                    ->where('downtime_hours', '>=', 8)
                    ->whereDate('date', '>=', now()->subDays(7))->limit(50)->get(),
            ],
            'LOW_PRODUCTION' => [
                'title' => 'Produksi 7 hari di bawah rata-rata 28 hari',
                'enabled' => true,
                'detect' => function () {
                    $avg28 = (float) \App\Models\ProductionBatch::where('status', 'POSTED')
                        ->whereDate('date', '>=', now()->subDays(27)->toDateString())->avg('net_output');
                    $avg7 = (float) \App\Models\ProductionBatch::where('status', 'POSTED')
                        ->whereDate('date', '>=', now()->subDays(6)->toDateString())->avg('net_output');
                    if ($avg28 > 0 && $avg7 < $avg28 * 0.7) {
                        return collect([(object) ['id' => 0, 'number' => 'Rata-rata 7h ' . round($avg7, 1) . ' vs 28h ' . round($avg28, 1)]]);
                    }
                    return collect();
                },
            ],
            'COMPLIANCE_EXPIRY' => [
                'title' => 'Compliance kedaluwarsa / segera (30 hari)',
                'enabled' => true,
                'detect' => fn () => \App\Models\ComplianceRegister::whereIn('status', ['EXPIRING_SOON', 'EXPIRED'])
                    ->whereNotNull('expiry_date')->limit(50)->get(),
            ],
        ];
    }
}
