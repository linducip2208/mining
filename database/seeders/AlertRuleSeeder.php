<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['MIN_STOCK', 'Stok mencapai batas minimum', 'Stok item di bawah min_stock (dihitung dari stock ledger)'],
            ['INVOICE_OVERDUE', 'Faktur melewati jatuh tempo', 'Faktur POSTED/PARTIALLY_PAID dengan due_date < hari ini'],
            ['DOCUMENT_EXPIRY', 'Dokumen/permit akan kadaluarsa', 'Dokumen LEGAL/PERMIT kadaluarsa dalam 30 hari'],
            ['MAINTENANCE_DUE', 'Jadwal pemeliharaan jatuh tempo', 'Jadwal aktif dengan next_due <= 7 hari ke depan'],
            ['PRICE_VARIANCE', 'Selisih harga abnormal', 'Variance PENDING dengan persentase >= 10%'],
            ['APPROVAL_PENDING', 'Persetujuan tertunda', 'ApprovalRequest PENDING lebih dari 3 hari'],
        ];

        foreach ($rules as [$code, $name, $desc]) {
            AlertRule::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'event_type' => $code, 'is_active' => true, 'config' => ['description' => $desc]]
            );
        }

        $this->command?->info('Alert rules: ' . AlertRule::count() . ' rules');
    }
}
