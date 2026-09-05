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
        ];
    }
}
