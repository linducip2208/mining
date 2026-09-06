<?php

namespace App\Support;

use Illuminate\Support\Str;

final class HumanLabel
{
    private const ACRONYMS = [
        'pph21' => 'PPh 21', 'bpjs' => 'BPJS', 'npwp' => 'NPWP', 'ppn' => 'PPN', 'pbb' => 'PBB',
        'nik' => 'NIK', 'sku' => 'SKU', 'coa' => 'COA', 'api' => 'API', 'url' => 'URL', 'gps' => 'GPS',
        'bbm' => 'BBM', 'hse' => 'HSE', 'k3' => 'K3', 'po' => 'PO', 'pr' => 'PR', 'do' => 'DO',
        'gr' => 'GR', 'ar' => 'AR', 'ap' => 'AP', 'hm' => 'HM', 'iot' => 'IoT', 'ai' => 'AI',
    ];

    private const FIELDS = [
        'site_id' => 'Site', 'pit_id' => 'Pit', 'company_id' => 'Perusahaan', 'branch_id' => 'Cabang',
        'division_id' => 'Divisi', 'department_id' => 'Departemen', 'employee_id' => 'Karyawan',
        'customer_id' => 'Customer', 'supplier_id' => 'Supplier', 'equipment_id' => 'Unit / Peralatan',
        'vehicle_id' => 'Kendaraan', 'warehouse_id' => 'Gudang', 'item_id' => 'Item', 'created_at' => 'Dibuat Pada',
        'updated_at' => 'Diperbarui Pada', 'approval_status' => 'Status Persetujuan', 'posting_status' => 'Status Posting',
        'reference_no' => 'Nomor Referensi', 'posting_date' => 'Tanggal Posting', 'net_output' => 'Output Bersih',
        'gross_weight' => 'Berat Kotor', 'tare_weight' => 'Berat Tara', 'unit_cost' => 'Biaya per Unit',
        'fuel_variance' => 'Selisih BBM', 'invoice_prefix' => 'Prefix Nomor Invoice', 'default_currency' => 'Mata Uang Default',
        'low_stock_threshold' => 'Batas Stok Minimum', 'fuel_variance_threshold' => 'Batas Toleransi Selisih BBM',
        'pph21_rate' => 'Tarif PPh 21', 'payroll.pph21_rate' => 'Tarif PPh 21', 'payroll.bpjs_health_employee_rate' => 'BPJS Kesehatan - Karyawan',
        'payroll.bpjs_health_company_rate' => 'BPJS Kesehatan - Perusahaan',
        'payroll.bpjs_employment_employee_rate' => 'BPJS Ketenagakerjaan - Karyawan',
        'payroll.bpjs_employment_company_rate' => 'BPJS Ketenagakerjaan - Perusahaan',
        'payroll.overtime_rate' => 'Tarif Lembur', 'payroll.payday' => 'Tanggal Pembayaran Gaji',
        'payroll.cutoff_day' => 'Tanggal Cut-Off Payroll', 'finance.fiscal_year_start' => 'Awal Tahun Fiskal',
        'sales.invoice_prefix' => 'Prefix Nomor Invoice', 'inventory.low_stock_threshold' => 'Batas Stok Minimum',
        'fuel.variance_threshold' => 'Batas Toleransi Selisih BBM',
    ];

    private const ENUMS = [
        'customer_deposit' => 'Deposit Customer', 'fuel_issue' => 'Pengeluaran BBM', 'stock_adjustment' => 'Penyesuaian Stok',
        'purchase_request' => 'Permintaan Pembelian', 'capital_expenditure' => 'Belanja Modal', 'operating_expense' => 'Biaya Operasional',
        'first_weigh' => 'Timbang Pertama', 'second_weigh' => 'Timbang Kedua', 'product' => 'Produk', 'material' => 'Material',
        'pass' => 'Lulus', 'fail' => 'Gagal', 'warning' => 'Peringatan', 'critical' => 'Kritis',
    ];

    public static function label(?string $value): string
    {
        if ($value === null || trim($value) === '') return "\u{2014}";
        if ($value === null || trim($value) === '') return '—';
        $key = Str::lower(trim($value));
        if (isset(self::FIELDS[$key])) return self::FIELDS[$key];
        if (isset(self::ENUMS[$key])) return self::ENUMS[$key];
        $parts = preg_split('/[._-]+/', $key) ?: [$key];
        $parts = array_map(fn ($part) => self::ACRONYMS[$part] ?? Str::ucfirst($part), $parts);
        return implode(' ', $parts);
    }
}
