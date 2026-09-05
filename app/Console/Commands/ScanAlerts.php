<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class ScanAlerts extends Command
{
    protected $signature = 'alert:scan {--dry : hanya tampilkan hasil tanpa kirim notifikasi}';

    protected $description = 'Pindai kondisi sistem (stok kritis, invoice overdue, dokumen kadaluarsa, dll) dan kirim notifikasi';

    public function handle(AlertService $service): int
    {
        $checks = [
            'MIN_STOCK' => fn () => \App\Models\Item::query()->where('min_stock', '>', 0)
                ->whereRaw('(SELECT COALESCE(SUM(qty_in - qty_out),0) FROM stock_ledger WHERE stock_ledger.item_id = items.id) < min_stock')->count(),
            'INVOICE_OVERDUE' => fn () => \App\Models\Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->whereDate('due_date', '<', now())->count(),
            'DOCUMENT_EXPIRY' => fn () => \App\Models\Document::whereIn('category', ['LEGAL', 'PERMIT'])->whereDate('expiry_date', '<=', now()->addDays(30))->count(),
            'MAINTENANCE_DUE' => fn () => \App\Models\MaintenanceSchedule::where('is_active', true)->whereDate('next_due', '<=', now()->addDays(7))->count(),
            'PRICE_VARIANCE' => fn () => \App\Models\PriceVariance::where('approval_status', 'PENDING')->where('variance_percentage', '>=', 10)->count(),
            'APPROVAL_PENDING' => fn () => \App\Models\ApprovalRequest::where('status', 'PENDING')->whereDate('submitted_at', '<=', now()->subDays(3))->count(),
        ];

        foreach ($checks as $code => $count) {
            $n = $count();
            $this->line(str_pad($code, 22) . " => {$n}");
        }

        if (!$this->option('dry')) {
            $summary = $service->run();
            $this->info('Notifikasi terkirim untuk: ' . (empty($summary) ? '(tidak ada temuan)' : json_encode($summary, JSON_UNESCAPED_UNICODE)));
        }

        return self::SUCCESS;
    }
}
