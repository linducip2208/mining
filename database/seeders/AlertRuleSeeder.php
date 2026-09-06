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
            ['FUEL_ANOMALY', 'Anomali konsumsi BBM', 'Fuel issue WARNING/CRITICAL 7 hari terakhir'],
            ['STOCK_VARIANCE', 'Variansi survei stockpile', 'Survei status INVESTIGATE'],
            ['EQUIPMENT_BREAKDOWN', 'Alat breakdown', 'Equipment status BREAKDOWN'],
            ['MAINTENANCE_OVERDUE', 'Maintenance terlewat', 'Jadwal next_due < hari ini'],
            ['CONTRACT_EXPIRY', 'Kontrak kedaluwarsa', 'Kontrak customer ACTIVE berakhir <= 30 hari'],
            ['BUDGET_EXCEEDED', 'Budget terlampaui', 'Terpakai (komit+aktual) >= 90%'],
            ['OVERDUE_AP', 'Hutang jatuh tempo', 'Tagihan POSTED/PARTIALLY_PAID due_date < hari ini'],
            ['QUALITY_FAILURE', 'QC gagal / hold', 'Quality hold status HOLD'],
            ['HIGH_DOWNTIME', 'Downtime tinggi', 'WO downtime >= 8 jam dalam 7 hari'],
            ['LOW_PRODUCTION', 'Produksi rendah', 'Rata-rata 7 hari < 70% rata-rata 28 hari'],
            ['COMPLIANCE_EXPIRY', 'Compliance kedaluwarsa', 'Register EXPIRING_SOON/EXPIRED'],
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
