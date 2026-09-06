<?php

namespace App\Support;

final class SettingCatalog
{
    public static function all(): array
    {
        $catalog = [
            'system.company_name' => self::text('Nama Perusahaan', 'Nama legal yang tampil pada dokumen dan halaman aplikasi.', 'Perusahaan', 'PT Tambang Sejahtera'),
            'branding.app_name' => self::text('Nama Aplikasi', 'Nama produk yang tampil pada browser dan navigasi.', 'Branding', 'Mining ERP Pro'),
            'branding.tagline' => self::text('Tagline Aplikasi', 'Kalimat singkat yang menjelaskan nilai aplikasi.', 'Branding', 'Integrated Mining ERP'),
            'branding.primary_color' => self::meta('Warna Utama', 'Warna aksen utama untuk tombol dan indikator.', 'Theme', 'color', '#f59e0b'),
            'payroll.ptkp_monthly' => self::meta('PTKP Bulanan', 'Nilai Penghasilan Tidak Kena Pajak per bulan.', 'Payroll & Pajak', 'currency', '4500000', 'Rp'),
            'payroll.pph21_rate' => self::meta('Tarif PPh 21', 'Tarif PPh 21 default untuk perhitungan payroll.', 'Payroll & Pajak', 'percentage', '5', '%'),
            'payroll.bpjs_health_employee_rate' => self::meta('BPJS Kesehatan — Karyawan', 'Persentase potongan BPJS kesehatan karyawan.', 'Payroll & Pajak', 'percentage', '1', '%'),
            'payroll.bpjs_health_company_rate' => self::meta('BPJS Kesehatan — Perusahaan', 'Persentase kontribusi BPJS kesehatan perusahaan.', 'Payroll & Pajak', 'percentage', '4', '%'),
            'payroll.bpjs_employment_employee_rate' => self::meta('BPJS Ketenagakerjaan — Karyawan', 'Persentase potongan BPJS ketenagakerjaan karyawan.', 'Payroll & Pajak', 'percentage', '2', '%'),
            'payroll.bpjs_employment_company_rate' => self::meta('BPJS Ketenagakerjaan — Perusahaan', 'Persentase kontribusi BPJS ketenagakerjaan perusahaan.', 'Payroll & Pajak', 'percentage', '5.7', '%'),
            'payroll.overtime_rate' => self::meta('Tarif Lembur', 'Pengali tarif lembur default per jam.', 'Payroll & Pajak', 'decimal', '1.5', 'x'),
            'payroll.payday' => self::meta('Tanggal Pembayaran Gaji', 'Tanggal pembayaran payroll bulanan.', 'Payroll & Pajak', 'integer', '25', 'tanggal'),
            'payroll.cutoff_day' => self::meta('Tanggal Cut-Off Payroll', 'Hari terakhir periode perhitungan payroll.', 'Payroll & Pajak', 'integer', '20', 'tanggal'),
            'finance.default_currency' => self::select('Mata Uang Default', 'Mata uang yang digunakan pada laporan dan transaksi.', 'Keuangan', 'IDR', ['IDR' => 'Rupiah (IDR)', 'USD' => 'Dolar Amerika (USD)']),
            'finance.fiscal_year_start' => array_merge(self::meta('Awal Tahun Fiskal', 'Bulan pertama periode fiskal perusahaan.', 'Keuangan', 'integer', '1', 'bulan'), ['validation' => 'integer|min:1|max:12']),
            'sales.invoice_prefix' => self::text('Prefix Nomor Invoice', 'Awalan nomor faktur penjualan.', 'Penjualan', 'INV-'),
            'sales.invoice_require_do' => self::meta('Faktur Wajib Memiliki Surat Jalan', 'Tentukan apakah faktur hanya dapat dibuat dari surat jalan.', 'Penjualan', 'boolean', 'true'),
            'inventory.allow_negative_stock' => self::meta('Izinkan Stok Negatif', 'Izinkan transaksi melewati saldo stok tersedia.', 'Inventory', 'boolean', 'false'),
            'inventory.low_stock_threshold' => self::meta('Batas Stok Minimum', 'Batas untuk memicu peringatan stok rendah.', 'Inventory', 'decimal', '0'),
            'inventory.default_warehouse_id' => self::meta('Gudang Default', 'Gudang utama untuk transaksi inventory.', 'Inventory', 'integer', '1'),
            'fuel.dip_threshold_pct' => self::meta('Batas Toleransi Selisih BBM', 'Persentase toleransi selisih hasil dip tangki.', 'Fleet & BBM', 'percentage', '2', '%'),
            'tax.default_sales_tax_code' => self::text('Kode Pajak Penjualan Default', 'Kode pajak yang digunakan sebagai tarif penjualan bawaan.', 'Payroll & Pajak', 'PPN11'),
            'hse.severity_levels' => self::meta('Tingkat Keparahan HSE', 'Daftar tingkat keparahan yang tersedia pada laporan HSE.', 'HSE & Compliance', 'textarea', 'LOW, MEDIUM, HIGH, CRITICAL'),
            'weighbridge.allow_weight_override' => self::meta('Izinkan Override Berat', 'Izinkan koreksi berat setelah timbang dengan audit trail.', 'Timbangan', 'boolean', 'true'),
            'weighbridge.void_require_approval' => self::meta('Pembatalan Timbangan Memerlukan Persetujuan', 'Wajibkan persetujuan untuk membatalkan tiket timbang.', 'Timbangan', 'boolean', 'true'),
            'budget.enforce' => self::select('Kontrol Anggaran', 'Perilaku ketika transaksi melewati anggaran.', 'Keuangan', 'warning', ['warning' => 'Peringatan', 'block' => 'Blokir transaksi']),
            'docs.public' => self::meta('Dokumentasi Publik', 'Izinkan pengguna tanpa login membuka dokumentasi.', 'Dokumen', 'boolean', 'true'),
            'notification.whatsapp_webhook_url' => self::meta('URL Webhook Notifikasi', 'Alamat webhook untuk pengiriman notifikasi eksternal.', 'Notifikasi', 'secret', ''),
            'developer_labels_enabled' => self::meta('Tampilkan Developer Key', 'Tampilkan key internal untuk kebutuhan troubleshooting.', 'Advanced', 'boolean', 'false', null, true),
        ];
        foreach ($catalog as $key => &$meta) {
            $meta['key'] = $key;
            $meta['label'] = str_replace("\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D", "\u{2014}", $meta['label']);
            $meta['help_text'] ??= null;
            $meta['options'] ??= [];
        }
        return $catalog;
    }

    public static function get(string $key): array
    {
        return self::all()[$key] ?? self::meta(HumanLabel::label($key), 'Pengaturan tambahan.', 'Advanced', 'text', '');
    }

    private static function meta(string $label, string $description, string $group, string $type, mixed $default = '', ?string $unit = null, bool $sensitive = false): array
    {
        return compact('label', 'description', 'group', 'type', 'default', 'unit', 'sensitive') + [
            'help_text' => null,
            'options' => [],
            'validation' => match ($type) {
                'percentage' => 'numeric|min:0|max:100',
                'currency', 'decimal' => 'numeric|min:0',
                'integer' => 'integer|min:1|max:31',
                'boolean' => 'boolean',
                'color' => ['regex:/^#[0-9a-fA-F]{6}$/'],
                'secret', 'text', 'textarea' => 'nullable|string|max:1000',
                default => 'nullable|string|max:255',
            },
        ];
    }

    private static function text(string $label, string $description, string $group, string $default = ''): array { return self::meta($label, $description, $group, 'text', $default); }
    private static function select(string $label, string $description, string $group, string $default, array $options): array
    {
        return array_merge(self::meta($label, $description, $group, 'select', $default), ['options' => $options]);
    }
}
