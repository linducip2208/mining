<?php

namespace App\Support;

/**
 * Stable setting keys with their human-facing contract.
 * Business code keeps using the key; this catalog owns presentation metadata.
 */
final class SettingCatalog
{
    public static function all(): array
    {
        $items = [];
        $add = static function (string $key, array $meta) use (&$items): void {
            $items[$key] = array_merge([
                'key' => $key, 'label' => HumanLabel::label($key),
                'description' => 'Konfigurasi aplikasi.', 'group' => 'Advanced',
                'type' => 'text', 'unit' => null, 'default' => '',
                'help_text' => null, 'options' => [], 'sensitive' => false,
                'validation' => 'nullable|string|max:1000',
            ], $meta, ['key' => $key]);
        };
        $text = static function (string $key, string $label, string $description, string $group, mixed $default = '', array $extra = []) use ($add): void {
            $add($key, array_merge(compact('label', 'description', 'group', 'default'), ['type' => 'text'], $extra));
        };
        $area = static function (string $key, string $label, string $description, string $group, mixed $default = '') use ($add): void {
            $add($key, compact('label', 'description', 'group', 'default') + ['type' => 'textarea', 'validation' => 'nullable|string|max:5000']);
        };
        $bool = static function (string $key, string $label, string $description, string $group, bool $default = false, array $extra = []) use ($add): void {
            $add($key, array_merge(compact('label', 'description', 'group', 'default'), ['type' => 'boolean', 'validation' => 'boolean'], $extra));
        };
        $number = static function (string $key, string $label, string $description, string $group, string $type = 'decimal', mixed $default = 0, ?string $unit = null, array $extra = []) use ($add): void {
            $validation = $type === 'percentage' ? 'numeric|min:0|max:100' : ($type === 'integer' ? 'integer|min:0' : 'numeric|min:0');
            $add($key, array_merge(compact('label', 'description', 'group', 'type', 'default', 'unit'), ['validation' => $validation], $extra));
        };
        $select = static function (string $key, string $label, string $description, string $group, mixed $default, array $options, array $extra = []) use ($add): void {
            $add($key, array_merge(compact('label', 'description', 'group', 'default', 'options'), ['type' => 'select', 'validation' => 'nullable|string|max:100'], $extra));
        };
        $image = static function (string $key, string $label, string $description, string $group) use ($add): void {
            $add($key, compact('label', 'description', 'group') + ['type' => 'image', 'default' => '', 'validation' => 'nullable|file|mimes:png,jpg,jpeg,webp,ico|max:5120']);
        };
        $secret = static function (string $key, string $label, string $description, string $group) use ($add): void {
            $add($key, compact('label', 'description', 'group') + ['type' => 'secret', 'default' => '', 'sensitive' => true, 'validation' => 'nullable|string|max:2000']);
        };
        $multi = static function (string $key, string $label, string $description, string $group, array $options, array $default = []) use ($add): void {
            $add($key, compact('label', 'description', 'group', 'options', 'default') + ['type' => 'multiselect', 'validation' => 'nullable|array']);
        };
        $color = static function (string $key, string $label, string $description, string $group, string $default = '#0f172a') use ($add): void {
            $add($key, compact('label', 'description', 'group', 'default') + ['type' => 'color', 'validation' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']]);
        };

        // General and company identity.
        $select('general.timezone', 'Zona Waktu', 'Zona waktu default untuk transaksi dan laporan.', 'Umum', 'Asia/Jakarta', ['Asia/Jakarta' => 'WIB — Asia/Jakarta', 'Asia/Makassar' => 'WITA — Asia/Makassar', 'Asia/Jayapura' => 'WIT — Asia/Jayapura']);
        $select('general.locale', 'Bahasa Aplikasi', 'Bahasa antarmuka utama aplikasi.', 'Umum', 'id', ['id' => 'Bahasa Indonesia', 'en' => 'English']);
        $select('general.date_format', 'Format Tanggal', 'Format tanggal pada tabel, laporan, dan dokumen.', 'Umum', 'd M Y', ['d M Y' => '06 Sep 2026', 'd/m/Y' => '06/09/2026', 'Y-m-d' => '2026-09-06']);
        $select('general.time_format', 'Format Waktu', 'Format jam pada aktivitas dan audit trail.', 'Umum', 'H:i', ['H:i' => '24 jam (14:30)', 'h:i A' => '12 jam (02:30 PM)']);
        $select('general.number_locale', 'Format Angka', 'Format angka untuk nominal, tonase, dan volume.', 'Umum', 'id-ID', ['id-ID' => 'Indonesia (12.842,50)', 'en-US' => 'English (12,842.50)']);
        $select('general.default_currency', 'Mata Uang Default', 'Mata uang utama untuk transaksi dan laporan.', 'Umum', 'IDR', ['IDR' => 'Rupiah (IDR)', 'USD' => 'Dolar Amerika (USD)']);
        $select('finance.default_currency', 'Mata Uang Default', 'Mata uang buku besar dan jurnal keuangan.', 'Keuangan', 'IDR', ['IDR' => 'Rupiah (IDR)', 'USD' => 'Dolar Amerika (USD)']);
        $select('general.currency_position', 'Posisi Simbol Mata Uang', 'Posisi simbol mata uang saat ditampilkan.', 'Umum', 'before', ['before' => 'Sebelum nominal (Rp 125.000)', 'after' => 'Sesudah nominal (125.000 Rp)']);
        $number('general.decimal_places', 'Jumlah Desimal', 'Jumlah angka desimal untuk tampilan angka.', 'Umum', 'integer', 2, 'digit', ['validation' => 'integer|min:0|max:4']);
        $text('system.company_name', 'Nama Perusahaan', 'Nama perusahaan yang tampil di aplikasi dan dokumen.', 'Perusahaan', 'PT Tambang Sejahtera');
        $text('system.company_legal_name', 'Nama Legal Perusahaan', 'Nama badan hukum resmi untuk dokumen bisnis.', 'Perusahaan', 'PT Tambang Sejahtera');
        $area('system.company_address', 'Alamat Perusahaan', 'Alamat kantor pusat atau identitas dokumen.', 'Perusahaan');
        foreach (['city' => 'Kota', 'province' => 'Provinsi', 'postal_code' => 'Kode Pos', 'phone' => 'Telepon Perusahaan', 'npwp' => 'NPWP Perusahaan', 'pic' => 'Penanggung Jawab', 'registration_no' => 'Nomor Registrasi'] as $suffix => $label) {
            $text('system.company_'.$suffix, $label, 'Informasi resmi perusahaan untuk dokumen dan komunikasi.', 'Perusahaan');
        }
        $text('system.company_email', 'Email Perusahaan', 'Alamat email resmi perusahaan.', 'Perusahaan', '', ['validation' => 'nullable|email|max:255']);
        $text('system.company_website', 'Website Perusahaan', 'Alamat website resmi perusahaan.', 'Perusahaan', '', ['validation' => 'nullable|url|max:255']);

        // White-label identity and CSS theme tokens.
        foreach ([
            'app_name' => ['Nama Aplikasi', 'Nama produk pada browser, sidebar, dan email.', 'Mining ERP Pro'],
            'app_short_name' => ['Nama Singkat Aplikasi', 'Nama ringkas untuk sidebar collapsed dan PWA.', 'Mining ERP'],
            'tagline' => ['Tagline Aplikasi', 'Kalimat singkat yang menjelaskan nilai aplikasi.', 'Integrated Mining ERP'],
            'copyright_text' => ['Teks Copyright', 'Teks copyright pada sidebar dan dokumen.', '© 2026 Mining ERP'],
            'powered_by_text' => ['Teks Powered By', 'Teks atribusi opsional untuk white-label.', ''],
            'powered_by_url' => ['URL Powered By', 'Tautan atribusi opsional.', ''],
        ] as $suffix => [$label, $description, $default]) {
            $text('branding.'.$suffix, $label, $description, 'Branding', $default, $suffix === 'powered_by_url' ? ['validation' => 'nullable|url|max:255'] : []);
        }
        foreach (['logo_main' => 'Logo Utama', 'logo_sidebar' => 'Logo Sidebar', 'logo_sidebar_collapsed' => 'Logo Sidebar Ringkas', 'logo_dark' => 'Logo Mode Gelap', 'logo_login' => 'Logo Halaman Login', 'favicon' => 'Favicon'] as $suffix => $label) {
            $image('branding.'.$suffix, $label, 'File gambar branding yang digunakan pada area terkait.', 'Branding');
        }
        foreach (['primary_color' => 'Warna Primer', 'accent_color' => 'Warna Aksen', 'sidebar_color' => 'Warna Sidebar', 'topbar_color' => 'Warna Topbar'] as $suffix => $label) {
            $color('branding.'.$suffix, $label, 'Warna visual yang dapat disesuaikan oleh pemilik aplikasi.', 'Branding', $suffix === 'primary_color' ? '#d97706' : '#0f172a');
        }
        $select('theme.default_mode', 'Mode Tampilan Default', 'Mode warna awal untuk pengguna baru.', 'Theme & Layout', 'system', ['system' => 'Ikuti perangkat', 'light' => 'Terang', 'dark' => 'Gelap']);
        foreach (['primary_color' => 'Warna Primer', 'accent_color' => 'Warna Aksen', 'sidebar_color' => 'Warna Sidebar', 'topbar_color' => 'Warna Topbar'] as $suffix => $label) {
            $color('theme.'.$suffix, $label, 'Token warna tema yang diterapkan melalui CSS variables.', 'Theme & Layout', $suffix === 'primary_color' ? '#d97706' : '#0f172a');
        }
        $select('theme.sidebar_style', 'Gaya Sidebar', 'Pilih gaya navigasi utama.', 'Theme & Layout', 'dark', ['dark' => 'Gelap', 'light' => 'Terang']);
        $bool('theme.sidebar_collapsed_default', 'Sidebar Ringkas Default', 'Buka aplikasi dengan sidebar dalam keadaan ringkas.', 'Theme & Layout');
        $select('theme.density', 'Kepadatan Tampilan', 'Atur ruang antar elemen pada layar kerja.', 'Theme & Layout', 'comfortable', ['compact' => 'Ringkas', 'comfortable' => 'Nyaman']);
        $select('theme.border_radius', 'Radius Komponen', 'Radius sudut kartu, input, dan tombol.', 'Theme & Layout', 'medium', ['small' => 'Kecil', 'medium' => 'Sedang', 'large' => 'Besar']);

        // Login, print, numbering, mail and PWA.
        $image('login.logo', 'Logo Login', 'Logo yang digunakan pada halaman masuk.', 'Login Page');
        $image('login.background_image', 'Latar Belakang Login', 'Gambar latar halaman masuk.', 'Login Page');
        $text('login.title', 'Judul Login', 'Judul utama halaman masuk.', 'Login Page', 'Selamat datang kembali');
        $text('login.subtitle', 'Subjudul Login', 'Teks pendukung di halaman masuk.', 'Login Page', 'Masuk untuk melanjutkan ke Mining ERP.');
        $text('login.footer_text', 'Footer Login', 'Teks footer halaman masuk.', 'Login Page', 'Akses aman untuk tim operasional.');
        $bool('login.show_company_name', 'Tampilkan Nama Perusahaan', 'Tampilkan identitas perusahaan di halaman login.', 'Login Page', true);
        $bool('login.show_tagline', 'Tampilkan Tagline', 'Tampilkan tagline pada halaman login.', 'Login Page', true);
        $image('document.logo', 'Logo Dokumen', 'Logo pada invoice, PO, DO, payslip, dan laporan.', 'Dokumen & Cetak');
        foreach (['header_text' => 'Teks Header Dokumen', 'footer_text' => 'Teks Footer Dokumen', 'watermark' => 'Watermark Dokumen'] as $suffix => $label) {
            $text('document.'.$suffix, $label, 'Teks branding pada dokumen cetak.', 'Dokumen & Cetak');
        }
        $bool('document.show_npwp', 'Tampilkan NPWP', 'Tampilkan NPWP perusahaan pada dokumen.', 'Dokumen & Cetak', true);
        $bool('document.show_signature', 'Tampilkan Tanda Tangan', 'Tampilkan blok tanda tangan pada dokumen.', 'Dokumen & Cetak', true);
        $text('document.signature_name', 'Nama Penandatangan', 'Nama default pada blok tanda tangan.', 'Dokumen & Cetak');
        $text('document.signature_title', 'Jabatan Penandatangan', 'Jabatan default pada blok tanda tangan.', 'Dokumen & Cetak');
        $image('document.signature_image', 'Gambar Tanda Tangan', 'Gambar tanda tangan untuk dokumen resmi.', 'Dokumen & Cetak');
        foreach (['invoice' => 'Invoice', 'po' => 'Purchase Order', 'pr' => 'Purchase Request', 'do' => 'Delivery Order', 'gr' => 'Goods Receipt', 'weighbridge' => 'Tiket Timbangan', 'journal' => 'Jurnal', 'work_order' => 'Work Order'] as $suffix => $label) {
            $prefix = $suffix === 'work_order' ? 'WO' : strtoupper($suffix);
            $text('numbering.'.$suffix.'_prefix', 'Prefix '.$label, 'Prefix nomor dokumen '.$label.'.', 'Nomor Dokumen', $prefix);
            $text('numbering.'.$suffix.'_format', 'Format '.$label, 'Format nomor '.$label.' menggunakan tahun, bulan, dan urutan.', 'Nomor Dokumen', '{PREFIX}/{YYYY}/{MM}/{SEQ:5}');
        }
        $text('email.sender_name', 'Nama Pengirim Email', 'Nama pengirim email aplikasi.', 'Email', 'Mining ERP');
        $text('email.sender_address', 'Alamat Pengirim Email', 'Alamat email pengirim aplikasi.', 'Email', '', ['validation' => 'nullable|email|max:255']);
        $text('email.reply_to', 'Alamat Balasan Email', 'Alamat tujuan balasan email.', 'Email', '', ['validation' => 'nullable|email|max:255']);
        $image('email.logo', 'Logo Email', 'Logo untuk template email.', 'Email');
        $area('email.footer', 'Footer Email', 'Footer template email.', 'Email');
        $text('email.smtp_host', 'Host SMTP', 'Host SMTP layanan email.', 'Email');
        $number('email.smtp_port', 'Port SMTP', 'Port layanan SMTP.', 'Email', 'integer', 587, null, ['validation' => 'integer|min:1|max:65535']);
        $text('email.smtp_username', 'Username SMTP', 'Username layanan SMTP.', 'Email');
        $secret('email.smtp_password', 'Password SMTP', 'Password SMTP tidak pernah ditampilkan atau dicatat mentah.', 'Email');
        $select('email.smtp_encryption', 'Enkripsi SMTP', 'Metode enkripsi koneksi SMTP.', 'Email', 'tls', ['' => 'Tanpa enkripsi', 'tls' => 'TLS', 'ssl' => 'SSL']);
        $bool('pwa.enabled', 'Aktifkan PWA', 'Aktifkan manifest dan instalasi aplikasi web.', 'PWA');
        $text('pwa.name', 'Nama PWA', 'Nama panjang PWA.', 'PWA', 'Mining ERP');
        $text('pwa.short_name', 'Nama Singkat PWA', 'Nama ringkas di launcher.', 'PWA', 'Mining ERP');
        $text('pwa.description', 'Deskripsi PWA', 'Deskripsi aplikasi progresif.', 'PWA', 'Pusat operasi dan keuangan tambang.');
        $image('pwa.icon_192', 'Ikon PWA 192px', 'Ikon PWA ukuran 192x192.', 'PWA');
        $image('pwa.icon_512', 'Ikon PWA 512px', 'Ikon PWA ukuran 512x512.', 'PWA');
        $color('pwa.theme_color', 'Warna Tema PWA', 'Warna tema manifest PWA.', 'PWA', '#0f172a');
        $color('pwa.background_color', 'Warna Latar PWA', 'Warna latar manifest PWA.', 'PWA', '#ffffff');

        // Core business controls. Existing keys remain unchanged.
        $number('payroll.ptkp_monthly', 'PTKP Bulanan', 'Nilai Penghasilan Tidak Kena Pajak per bulan.', 'Payroll & Pajak', 'currency', 4500000, 'Rp');
        $number('payroll.pph21_rate', 'Tarif PPh 21', 'Tarif PPh 21 default untuk perhitungan payroll.', 'Payroll & Pajak', 'percentage', 5, '%');
        foreach ([['health_employee', 'BPJS Kesehatan - Karyawan', 1], ['health_company', 'BPJS Kesehatan - Perusahaan', 4], ['employment_employee', 'BPJS Ketenagakerjaan - Karyawan', 2], ['employment_company', 'BPJS Ketenagakerjaan - Perusahaan', 5.7]] as [$suffix, $label, $default]) {
            $number('payroll.bpjs_'.$suffix.'_rate', $label, 'Persentase kontribusi '.$label.'.', 'Payroll & Pajak', 'percentage', $default, '%');
        }
        $number('payroll.overtime_rate', 'Tarif Lembur', 'Pengali tarif lembur default per jam.', 'Payroll & Pajak', 'decimal', 1.5, 'x');
        $number('payroll.payday', 'Tanggal Pembayaran Gaji', 'Tanggal pembayaran payroll bulanan.', 'Payroll & Pajak', 'integer', 25, 'tanggal', ['validation' => 'integer|min:1|max:31']);
        $number('payroll.cutoff_day', 'Tanggal Cut-Off Payroll', 'Hari terakhir periode perhitungan payroll.', 'Payroll & Pajak', 'integer', 20, 'tanggal', ['validation' => 'integer|min:1|max:31']);
        $number('finance.fiscal_year_start', 'Awal Tahun Fiskal', 'Bulan pertama periode fiskal perusahaan.', 'Keuangan', 'integer', 1, 'bulan', ['validation' => 'integer|min:1|max:12']);
        foreach (['lock_posted_journal' => 'Kunci Jurnal Terposting', 'require_balanced_journal' => 'Wajibkan Jurnal Seimbang', 'period_lock_enabled' => 'Kunci Periode Keuangan'] as $suffix => $label) {
            $bool('finance.'.$suffix, $label, 'Perlindungan akuntansi untuk '.$label.'.', 'Keuangan', true);
        }
        $bool('finance.allow_backdate', 'Izinkan Backdate', 'Izinkan transaksi mundur dalam periode terbuka.', 'Keuangan');
        $number('finance.backdate_days', 'Batas Hari Backdate', 'Maksimum hari mundur transaksi.', 'Keuangan', 'integer', 0, 'hari');
        $select('budget.enforce', 'Kontrol Anggaran', 'Perilaku ketika transaksi melewati anggaran.', 'Keuangan', 'warning', ['warning' => 'Peringatan', 'block' => 'Blokir transaksi']);
        $text('sales.invoice_prefix', 'Prefix Nomor Invoice', 'Awalan nomor faktur penjualan.', 'Penjualan', 'INV-');
        foreach (['invoice_require_do' => 'Invoice Wajib Memiliki Surat Jalan', 'credit_limit_enforced' => 'Terapkan Batas Kredit', 'allow_negative_customer_balance' => 'Izinkan Saldo Customer Minus', 'require_contract' => 'Wajibkan Kontrak Penjualan'] as $suffix => $label) {
            $bool('sales.'.$suffix, $label, 'Aturan proses penjualan '.$label.'.', 'Penjualan', $suffix === 'invoice_require_do');
        }
        $number('sales.default_payment_term_days', 'Jatuh Tempo Default', 'Jangka waktu pembayaran default.', 'Penjualan', 'integer', 30, 'hari');
        foreach (['allow_negative_stock' => 'Izinkan Stok Negatif', 'require_approval_adjustment' => 'Penyesuaian Stok Wajib Approval', 'require_approval_transfer' => 'Transfer Stok Wajib Approval'] as $suffix => $label) {
            $bool('inventory.'.$suffix, $label, 'Aturan inventory '.$label.'.', 'Inventory', $suffix === 'require_approval_adjustment');
        }
        $number('inventory.low_stock_threshold', 'Batas Stok Minimum', 'Batas untuk memicu peringatan stok rendah.', 'Inventory');
        $select('inventory.costing_method', 'Metode Penilaian Persediaan', 'Metode costing yang didukung sistem.', 'Inventory', 'AVERAGE', ['AVERAGE' => 'Rata-rata bergerak', 'FIFO' => 'FIFO']);
        $text('tax.default_sales_tax_code', 'Kode Pajak Penjualan Default', 'Kode pajak default untuk penjualan.', 'Pajak', 'PPN11');
        $text('tax.default_purchase_tax_code', 'Kode Pajak Pembelian Default', 'Kode pajak default untuk pembelian.', 'Pajak', 'PPN11');
        $bool('tax.npwp_required', 'NPWP Wajib', 'Wajibkan NPWP pada transaksi pajak.', 'Pajak');
        $bool('tax.tax_invoice_enabled', 'Aktifkan Faktur Pajak', 'Aktifkan field dan alur faktur pajak.', 'Pajak');
        $select('procurement.po_over_receipt_tolerance', 'Toleransi Penerimaan Berlebih', 'Perilaku penerimaan barang melebihi PO.', 'Pengadaan', 'warning', ['warning' => 'Peringatkan', 'block' => 'Blokir']);
        $number('procurement.price_variance_tolerance', 'Toleransi Selisih Harga', 'Persentase toleransi selisih harga PO.', 'Pengadaan', 'percentage', 5, '%');
        foreach (['require_approved_pr' => 'Wajib PR Disetujui', 'require_vendor' => 'Wajib Vendor', 'three_way_match_enabled' => 'Aktifkan Three-Way Match'] as $suffix => $label) {
            $bool('procurement.'.$suffix, $label, 'Aturan pengadaan '.$label.'.', 'Pengadaan', $suffix !== 'three_way_match_enabled');
        }
        foreach ([['dip_threshold_pct', 'Toleransi Selisih Dip BBM', 2], ['max_variance_pct', 'Maksimum Selisih BBM', 5]] as [$suffix, $label, $default]) {
            $number('fuel.'.$suffix, $label, 'Batas toleransi konsumsi BBM.', 'Fleet & BBM', 'percentage', $default, '%');
        }
        foreach (['require_hm' => 'Wajib HM', 'require_operator' => 'Wajib Operator', 'require_vehicle' => 'Wajib Unit', 'negative_tank_allowed' => 'Izinkan Tangki Minus'] as $suffix => $label) {
            $bool('fuel.'.$suffix, $label, 'Aturan pengeluaran BBM '.$label.'.', 'Fleet & BBM', $suffix !== 'negative_tank_allowed');
        }
        $number('fleet.maintenance_warning_hours', 'Peringatan Maintenance', 'Jam operasi sebelum jadwal maintenance.', 'Maintenance', 'integer', 50, 'HM');
        $number('fleet.service_due_hours', 'Batas Service', 'Jam operasi saat service jatuh tempo.', 'Maintenance', 'integer', 500, 'HM');
        $number('fleet.inactive_days_threshold', 'Batas Unit Tidak Aktif', 'Hari hingga unit ditandai tidak aktif.', 'Fleet & BBM', 'integer', 30, 'hari');
        $select('mining.production_unit', 'Satuan Produksi', 'Satuan default output produksi tambang.', 'Operasi Tambang', 'ton', ['ton' => 'Ton', 'm3' => 'Meter kubik']);
        $add('mining.production_cutoff_time', ['label' => 'Cut-Off Produksi', 'description' => 'Jam cut-off pergantian hari produksi.', 'group' => 'Operasi Tambang', 'type' => 'time', 'default' => '06:00', 'validation' => 'date_format:H:i']);
        $number('mining.stock_reconciliation_tolerance', 'Toleransi Rekonsiliasi Stokpile', 'Batas selisih rekonsiliasi stockpile.', 'Operasi Tambang', 'percentage', 1, '%');
        $bool('mining.require_supervisor_approval', 'Approval Supervisor Produksi', 'Wajibkan verifikasi supervisor pada produksi.', 'Operasi Tambang', true);
        foreach (['allow_weight_override' => 'Izinkan Override Berat', 'void_require_approval' => 'Void Timbangan Wajib Approval', 'auto_capture_enabled' => 'Auto Capture Timbangan'] as $suffix => $label) {
            $bool('weighbridge.'.$suffix, $label, 'Aturan proses timbangan '.$label.'.', 'Timbangan', $suffix !== 'auto_capture_enabled');
        }
        $number('weighbridge.stable_weight_seconds', 'Detik Berat Stabil', 'Durasi berat harus stabil sebelum capture.', 'Timbangan', 'integer', 5, 'detik');
        $number('weighbridge.minimum_weight', 'Berat Minimum', 'Berat minimum yang dapat diterima.', 'Timbangan', 'decimal', 0, 'kg');
        $number('weighbridge.maximum_variance_pct', 'Maksimum Variansi Berat', 'Variansi maksimum antar penimbangan.', 'Timbangan', 'percentage', 2, '%');
        $select('weighbridge.device_mode', 'Mode Perangkat Timbangan', 'Mode koneksi perangkat timbangan.', 'Timbangan', 'manual', ['manual' => 'Manual', 'serial' => 'Serial', 'api' => 'API', 'iot' => 'IoT']);
        foreach (['permit_expiry_warning_days' => 'Peringatan Masa Berlaku Permit', 'medical_expiry_warning_days' => 'Peringatan Medical', 'training_expiry_warning_days' => 'Peringatan Training'] as $suffix => $label) {
            $number('hse.'.$suffix, $label, 'Hari sebelum masa berlaku berakhir.', 'HSE & Compliance', 'integer', 30, 'hari');
        }
        $bool('hse.require_investigation', 'Wajib Investigasi', 'Wajibkan investigasi pada insiden tertentu.', 'HSE & Compliance', true);
        $bool('hse.require_supervisor_verification', 'Wajib Verifikasi Supervisor', 'Wajibkan verifikasi supervisor atas tindakan HSE.', 'HSE & Compliance', true);
        $multi('hse.severity_levels', 'Tingkat Keparahan HSE', 'Tingkat keparahan pada laporan HSE.', 'HSE & Compliance', ['LOW' => 'Rendah', 'MEDIUM' => 'Sedang', 'HIGH' => 'Tinggi', 'CRITICAL' => 'Kritis'], ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);

        // Safe model references, notifications, security and feature flags.
        $add('inventory.default_warehouse_id', ['label' => 'Gudang Default', 'description' => 'Gudang utama untuk transaksi inventory.', 'group' => 'Inventory', 'type' => 'model_select', 'model' => 'warehouse', 'label_column' => 'name', 'value_column' => 'id', 'default' => null, 'validation' => 'nullable|integer|exists:warehouses,id']);
        $add('mining.default_site_id', ['label' => 'Site Default', 'description' => 'Site utama untuk operasi tambang.', 'group' => 'Operasi Tambang', 'type' => 'model_select', 'model' => 'site', 'label_column' => 'name', 'value_column' => 'id', 'default' => null, 'validation' => 'nullable|integer|exists:sites,id']);
        $add('mining.default_pit_id', ['label' => 'Pit Default', 'description' => 'Pit utama untuk aktivitas produksi.', 'group' => 'Operasi Tambang', 'type' => 'model_select', 'model' => 'pit', 'label_column' => 'name', 'value_column' => 'id', 'default' => null, 'validation' => 'nullable|integer|exists:pits,id']);
        foreach (['email_enabled' => 'Notifikasi Email', 'whatsapp_enabled' => 'Notifikasi WhatsApp', 'in_app_enabled' => 'Notifikasi Dalam Aplikasi', 'approval_enabled' => 'Notifikasi Approval', 'low_stock_enabled' => 'Peringatan Stok Minimum', 'fuel_variance_enabled' => 'Peringatan Selisih BBM', 'hse_enabled' => 'Peringatan HSE', 'maintenance_enabled' => 'Peringatan Maintenance', 'contract_expiry_enabled' => 'Peringatan Kontrak'] as $suffix => $label) {
            $bool('notification.'.$suffix, $label, 'Aktifkan '.$label.' untuk pengguna yang berwenang.', 'Notifikasi', true);
        }
        $multi('notification.channels', 'Kanal Notifikasi', 'Kanal yang diaktifkan untuk pengiriman notifikasi.', 'Notifikasi', ['in_app' => 'Dalam aplikasi', 'email' => 'Email', 'whatsapp' => 'WhatsApp'], ['in_app']);
        $number('notification.reminder_days', 'Hari Pengingat', 'Berapa hari sebelum jatuh tempo pengingat dikirim.', 'Notifikasi', 'integer', 3, 'hari');
        $number('notification.escalation_hours', 'Jam Eskalasi', 'Batas waktu sebelum notifikasi dieskalasikan.', 'Notifikasi', 'integer', 24, 'jam');
        $number('security.session_timeout', 'Batas Waktu Sesi', 'Durasi sesi login sebelum harus masuk kembali.', 'Keamanan', 'integer', 120, 'menit');
        $number('security.password_min_length', 'Panjang Minimum Password', 'Panjang minimum password baru.', 'Keamanan', 'integer', 8, 'karakter', ['validation' => 'integer|min:8|max:128']);
        foreach (['password_require_uppercase' => 'Wajib Huruf Besar', 'password_require_number' => 'Wajib Angka', 'password_require_symbol' => 'Wajib Simbol', 'mfa_enabled' => 'Aktifkan MFA'] as $suffix => $label) {
            $bool('security.'.$suffix, $label, 'Terapkan kebijakan keamanan.', 'Keamanan');
        }
        $number('security.max_login_attempts', 'Maksimum Percobaan Login', 'Jumlah percobaan gagal sebelum akun dikunci.', 'Keamanan', 'integer', 5, 'percobaan');
        $number('security.lockout_minutes', 'Durasi Penguncian Akun', 'Durasi akun terkunci setelah percobaan gagal.', 'Keamanan', 'integer', 30, 'menit');
        $number('security.force_password_change_days', 'Masa Berlaku Password', 'Hari hingga pengguna diminta mengganti password.', 'Keamanan', 'integer', 0, 'hari');
        $bool('developer_labels_enabled', 'Tampilkan Developer Key', 'Tampilkan key internal hanya untuk Super Admin.', 'Advanced');
        $bool('docs.public', 'Dokumentasi Publik', 'Izinkan pengguna tanpa login membuka dokumentasi.', 'Dokumen & Cetak', true);
        foreach (['fleet' => 'Armada', 'fuel' => 'BBM', 'hse' => 'HSE', 'quality' => 'Quality Control', 'dispatch' => 'Dispatch', 'ai' => 'AI', 'telematics' => 'Telematics'] as $module => $label) {
            $bool('modules.'.$module.'_enabled', 'Aktifkan Modul '.$label, 'Tampilkan dan izinkan akses ke modul '.$label.'.', 'Integrasi', true);
        }
        $bool('backup.enabled', 'Aktifkan Backup', 'Aktifkan backup hanya jika worker backup tersedia.', 'Backup & Data', false, ['help_text' => 'Belum ada backup cloud aktif pada instalasi ini.']);
        $bool('backup.database_enabled', 'Backup Database', 'Sertakan database pada backup.', 'Backup & Data', true);
        $bool('backup.files_enabled', 'Backup File', 'Sertakan file aplikasi pada backup.', 'Backup & Data', true);
        $number('backup.retention_days', 'Retensi Backup', 'Lama penyimpanan backup.', 'Backup & Data', 'integer', 30, 'hari');
        $secret('notification.whatsapp_webhook_url', 'Webhook WhatsApp', 'Alamat webhook notifikasi eksternal.', 'Integrasi');

        foreach ($items as &$meta) {
            $meta['default_value'] = $meta['default'];
        }
        unset($meta);

        return $items;
    }

    public static function get(string $key): array
    {
        return self::all()[$key] ?? [
            'key' => $key, 'label' => HumanLabel::label($key),
            'description' => 'Pengaturan tambahan yang belum memiliki metadata khusus.',
            'group' => 'Advanced', 'type' => 'text', 'unit' => null,
            'default' => '', 'default_value' => '', 'help_text' => null, 'options' => [],
            'sensitive' => false, 'validation' => 'nullable|string|max:1000',
        ];
    }
}
