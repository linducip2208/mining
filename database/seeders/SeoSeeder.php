<?php

namespace Database\Seeders;

use App\Models\SeoFeature;
use App\Models\SeoIndustry;
use App\Models\SeoKeyword;
use App\Models\SeoLocation;
use App\Models\SeoUseCase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Programmatic SEO catalog data. Idempotent via updateOrCreate on slugs.
 * Feature claims mirror implemented modules only (see docs/FINAL_FLOW_AUDIT.md).
 */
class SeoSeeder extends Seeder
{
    public function run(): void
    {
        $this->features();
        $this->industries();
        $this->locations();
        $this->useCases();
        $this->keywords();
    }

    protected function features(): void
    {
        $rows = [
            ['Mining Operations', 'Operasi penambangan harian per site, pit, dan shift.', 'Tonase produksi tercatat manual dan tersebar.', 'Aktivitas tercatat terstruktur dan mengalir ke stok.', ['Mining', 'Stockpile', 'Production'], 'mining', 10],
            ['Weighbridge', 'Timbangan truk dua tahap dengan NET otomatis.', 'Data timbangan tidak terhubung ke pengiriman.', 'Tiket FIRST/SECOND WEIGH terintegrasi DO dan cetak thermal.', ['Dispatch', 'Weighbridge', 'Delivery Order'], 'weighbridge', 10],
            ['Weighbridge Printer', 'Cetak tiket thermal 58/80mm dan reprint ter-audit.', 'Cetak ulang tak terkontrol dan tak tercatat.', 'Print job tercatat dengan reprint count dan alasan.', ['Weighbridge', 'Print'], 'printer', 40],
            ['Crusher', 'Batch crusher input raw menjadi produk.', 'Hasil crusher tak terpantau dan susut tak terukur.', 'Batch mencatat input, output, loss, dan scrap otomatis.', ['Stockpile', 'Crusher', 'Inventory'], 'production', 20],
            ['Production', 'Batch produksi dengan NET = GROSS − LOSS − SCRAP.', 'Perhitungan hasil produksi manual dan rawan salah.', 'Posting otomatis: stok raw keluar, FG masuk, jurnal konversi.', ['Production', 'Inventory', 'Accounting'], 'production', 10],
            ['Stockpile', 'Saldo dan movement stockpile plus survei.', 'Selisih survei vs sistem tak tertangani.', 'Ledger pile, movement antar pile-gudang, adjustment survei.', ['Mining', 'Stockpile', 'Production'], 'inventory', 20],
            ['Inventory', 'Multi-gudang dengan kartu stok bersaldo berjalan.', 'Stok antar gudang tak terpantau dan minus tak terdeteksi.', 'Ledger append-only, transfer, opname, reservasi, reorder point.', ['Inventory', 'Sales', 'Accounting'], 'inventory', 10],
            ['Warehouse', 'Manajemen gudang multi-site terpusat.', 'Stok tersebar tanpa visibilitas pusat.', 'Saldo per gudang, transfer, dan nilai stok terpusat.', ['Inventory'], 'inventory', 30],
            ['Procurement', 'Pengadaan dari PR hingga pembayaran vendor.', 'Proses beli manual dan status tak terpantau.', 'PR → PO → GRN → Bill → Pay dalam satu rantai terdokumentasi.', ['Procurement', 'Inventory', 'Accounting'], 'procurement', 10],
            ['Purchase Request', 'Permintaan pembelian dengan approval dan komitmen budget.', 'Permintaan via chat tanpa jejak dan tanpa cek budget.', 'PR ter-submit ke approval center dengan komitmen budget otomatis.', ['Budget', 'Procurement'], 'purchase_request', 20],
            ['Purchase Order', 'Order pembelian dengan status penerimaan otomatis.', 'Status terima PO tak terpantau.', 'Approve PO, GRN memperbarui PARTIALLY_RECEIVED/COMPLETED.', ['Procurement', 'Inventory'], 'purchase_order', 20],
            ['Goods Receipt', 'Penerimaan barang dengan QC dan anti over-receipt.', 'Barang datang tanpa dicatat dan tanpa QC.', 'GRN posting menambah stok moving-average dan rollup status PO.', ['Procurement', 'Inventory'], 'procurement', 30],
            ['Vendor Bill', 'Tagihan vendor dengan jurnal dan kontrol budget.', 'Tagihan menumpuk tanpa validasi over-billing.', 'Posting Dr Inventory/PPN Cr AP, consume budget, void reverse.', ['Procurement', 'Accounting'], 'procurement', 30],
            ['Sales Order', 'Order penjualan dengan guard kontrak dan harga berjenjang.', 'Order manual rawan over-contract dan salah harga.', 'Submit → approve → reservasi stok otomatis.', ['Sales', 'Inventory'], 'sales_order', 10],
            ['Delivery Order', 'Surat jalan anti over-delivery dengan gate QC.', 'Pengiriman melebihi order dan lolos tanpa QC.', 'Complete DO: stok keluar, SO ter-update, tiket terposting.', ['Sales', 'Weighbridge', 'Inventory'], 'delivery_order', 10],
            ['Invoice', 'Faktur dari qty terkirim dengan PPN dan jurnal otomatis.', 'Faktur dan accounting terpisah dan terlambat.', 'Satu SO satu faktur aktif; Dr AR Cr Revenue/PPN; alokasi deposit.', ['Sales', 'Accounting'], 'invoice', 10],
            ['Customer Deposit', 'Uang muka customer dengan ledger anti-overdraft.', 'Deposit tercatat manual dan rawan dipakai berlebih.', 'DEPOSIT_IN/USED/REFUND plus jurnal dan auto-alokasi faktur.', ['Sales', 'Accounting'], 'deposit', 30],
            ['Finance', 'Keuangan terintegrasi dari seluruh transaksi.', 'Laporan keuangan disusun manual akhir bulan.', 'Jurnal otomatis semua modul mengalir ke laporan.', ['Accounting'], 'finance', 10],
            ['Accounting', 'Double-entry dengan validasi dan reversal aman.', 'Jurnal tak balance dan tak bisa ditelusur balik.', 'Posting tervalidasi, periode terkunci, reversal mirror.', ['Accounting'], 'journal', 10],
            ['Cash Flow', 'Arus kas berbasis buku kas.', 'Posisi kas tak terpantau harian.', 'Mutasi kas tercatat per transaksi dan terlaporkan.', ['Finance'], 'finance', 40],
            ['General Ledger', 'Buku besar per akun terdokumentasi.', 'Mutasi akun sulit ditelusur.', 'Ledger Pajek: semua jurnal terpusat per COA.', ['Accounting'], 'ledger', 40],
            ['AR/AP', 'Piutang dan utang dengan aging.', 'Piutang overdue dan utang jatuh tempo terlewat.', 'Aging AR/AP, alokasi FIFO, status PAID terpantau.', ['Finance'], 'finance', 30],
            ['Tax', 'PPN otomatis dan pajak manual terkonfigurasi.', 'Pajak dihitung manual saat SPT.', 'PPN keluaran/masukan otomatis; tarif configurable.', ['Accounting'], 'tax', 40],
            ['HR', 'Data karyawan terpusat multi-site.', 'Data karyawan tersebar di file.', 'NIP, posisi, shift, gaji, bank dalam satu master.', ['HR'], 'employee', 20],
            ['Attendance', 'Absensi manual plus impor fingerprint.', 'Kehadiran tak terdokumentasi rapi.', 'Check-in/out, keterlambatan otomatis dari shift.', ['HR', 'Payroll'], 'attendance', 20],
            ['Fingerprint', 'Impor CSV mesin fingerprint.', 'Data mesin tak masuk sistem.', 'Impor massal dengan perhitungan keterlambatan.', ['HR'], 'attendance', 40],
            ['Payroll', 'Penggajian DRAFT hingga PAID dengan jurnal.', 'Hitung gaji manual di spreadsheet.', 'Kalkulasi, approve, posting Dr Salary Exp, bayar.', ['HR', 'Accounting'], 'payroll', 10],
            ['Operator Incentive', 'Insentif operator basis tonase/shift/target.', 'Insentif dihitung terpisah dari payroll.', 'Wajib APPROVED sebelum masuk payroll.', ['HR', 'Payroll'], 'incentive', 30],
            ['Fleet', 'Armada dan equipment dengan meter log.', 'HM/KM unit tak tercatat sistematis.', 'Meter maju anti-mundur, inspeksi, status breakdown.', ['Fleet'], 'fleet', 10],
            ['Heavy Equipment', 'Master alat berat multi-site.', 'Kondisi alat tak terpantau pusat.', 'Status lifecycle AVAILABLE hingga RETIRED.', ['Fleet', 'Maintenance'], 'fleet', 20],
            ['Fuel', 'BBM: terima, issue, transfer, dip tangki.', 'BBM rawan leakage tanpa variance control.', 'Issue cek saldo + avg-cost, L/H vs standar, notif anomali.', ['Fleet', 'Accounting'], 'fuel', 10],
            ['BBM', 'Kontrol BBM unit dan tangki.', 'Konsumsi per unit tak terukur.', 'Receipt, issue per equipment, transfer, stock opname tangki.', ['Fuel'], 'fuel', 20],
            ['Tire', 'Manajemen ban: pasang, rotasi, repair.', 'Umur dan posisi ban tak terpantau.', 'Movement tercatat dengan guard slot ganda.', ['Fleet', 'Maintenance'], 'tire', 30],
            ['Maintenance', 'Pemeliharaan preventif dan korektif.', 'Jadwal servis terlewat dan PM tak jalan.', 'Jadwal ber-next_due otomatis men-generate WO draft.', ['Maintenance'], 'maintenance', 10],
            ['Work Order', 'WO task, teknisi, dan sparepart.', 'Pekerjaan bengkel tak terdokumentasi.', 'Flow APPROVED→IN_PROGRESS→COMPLETED dengan guard status.', ['Maintenance'], 'work_order', 10],
            ['Sparepart', 'Issue sparepart dengan jurnal otomatis.', 'Pakai part tanpa catat biaya.', 'Stok keluar + cost + Dr Maintenance Exp.', ['Maintenance', 'Inventory', 'Accounting'], 'maintenance', 20],
            ['Dispatch', 'Dispatch trip dan ritase hauling.', 'Ritase hauling tercatat manual.', 'Status trip berjenjang dan link tiket timbang.', ['Dispatch', 'Weighbridge'], 'dispatch', 20],
            ['Hauling', 'Pengangkutan material pit ke stockpile/port.', 'Tonase angkut tak terekonsiliasi.', 'Trip terdispatch, tertimbang, dan terekonsiliasi.', ['Dispatch'], 'dispatch', 20],
            ['Quality Control', 'Uji sampel vs spesifikasi dengan hold otomatis.', 'Mutu lolos tanpa gate sistem.', 'FAIL otomatis HOLD dan blokir delivery.', ['Quality', 'Sales'], 'quality', 20],
            ['Laboratory', 'Hasil lab dan CoA terdokumentasi.', 'Hasil lab terpisah dari pengiriman.', 'CoA hanya terbit untuk sampel PASS.', ['Quality'], 'quality', 40],
            ['Cost per Ton', 'Biaya per ton dari data aktual.', 'HPP tambang tak terukur.', 'Fuel + maintenance + depresiasi + payroll dibagi output.', ['Finance', 'Production'], 'cost', 20],
            ['Contract', 'Kontrak customer, supplier, dan hauling.', 'Penjualan melebihi kontrak tak terdeteksi.', 'Guard over-contract dengan override permission.', ['Sales', 'Procurement'], 'contract', 40],
            ['Budget', 'Budget OPEX/CAPEX dengan komitmen dan variance.', 'Realisasi melebihi budget tanpa peringatan.', 'Komitmen PR/PO, actual dari jurnal, used% terpantau.', ['Budget', 'Accounting'], 'budget', 10],
            ['HSE', 'Insiden, permit, dan aksi korektif.', 'Insiden K3 tak terdokumentasi dan tak terverifikasi.', 'Lapor → investigasi → aksi → verifikasi → close.', ['HSE'], 'hse', 20],
            ['K3', 'Keselamatan dan kesehatan kerja terdokumentasi.', 'Temuan K3 tak tertindaklanjuti.', 'Corrective action wajib PIC, due date, dan evidence.', ['HSE'], 'hse', 30],
            ['Compliance', 'Registrasi dan reminder kepatuhan.', 'Izin kedaluwarsa tanpa peringatan.', 'Kalender compliance dengan reminder harian.', ['HSE'], 'compliance', 40],
            ['Approval', 'Approval center multi-modul terpusat.', 'Persetujuan manual tanpa jejak.', 'Workflow, delegasi, auto-approve, notifikasi.', ['Approval'], 'approval', 10],
            ['Audit Trail', 'Jejak aksi immutable per modul.', 'Siapa mengubah apa tak terlacak.', 'Log POST/APPROVE/PRINT/VOID dengan user dan IP.', ['Audit'], 'audit', 30],
            ['Document Management', 'Surat dan dokumen legal bernomor otomatis.', 'Dokumen tersebar tanpa versi.', 'Penomoran, attachment private, expiry reminder.', ['Document'], 'document', 40],
            ['CSR', 'Program CSR hingga beban ter-jurnal.', 'Dana CSR tak terlapor.', 'Proposal → aktivitas → Dr CSR Exp.', ['CSR', 'Accounting'], 'csr', 50],
            ['Reporting', 'Laporan operasional dan keuangan.', 'Laporan terlambat dan tak konsisten.', 'Filter periode, cetak/PDF, export CSV, audit export.', ['Report'], 'report', 20],
            ['Executive Dashboard', 'Command center eksekutif real-time.', 'Direksi menunggu laporan mingguan.', 'KPI produksi, penjualan, biaya, approval tertunda.', ['Dashboard'], 'dashboard', 10],
            ['Printer', 'Printer A4 dan thermal terdaftar per site.', 'Setting printer tiap workstation berantakan.', 'Device manager, mapping dokumen, test print.', ['Printer'], 'printer', 40],
            ['PWA', 'Aplikasi web terinstal dengan mode offline.', 'Akses lapangan butuh browser terus.', 'Manifest, service worker, halaman offline.', ['PWA'], 'pwa', 50],
        ];

        foreach ($rows as $i => [$name, $short, $problem, $solution, $workflow, $module, $priority]) {
            SeoFeature::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'short_description' => $short,
                'business_problem' => $problem, 'solution' => $solution,
                'workflow' => $workflow, 'related_module' => $module,
                'implemented' => true, 'marketing_enabled' => true, 'priority' => $priority,
            ]);
        }

        // Enrich short workflows to full operational chains (quality gate: min 3).
        $chains = [
            'work-order' => ['Jadwal Maintenance', 'Work Order', 'Sparepart', 'Cost per Ton'],
            'accounting' => ['Invoice', 'Vendor Bill', 'Jurnal', 'General Ledger'],
            'fuel' => ['Penerimaan BBM', 'Issue ke Unit', 'Variance L/H', 'Cost per Ton'],
            'bbm' => ['Penerimaan BBM', 'Issue ke Unit', 'Opname Tangki', 'Biaya per Ton'],
            'maintenance' => ['Jadwal', 'Work Order', 'Sparepart', 'Downtime'],
            'budget' => ['Budget', 'Komitmen PR/PO', 'Actual Jurnal', 'Variance'],
            'approval' => ['Pengajuan', 'Approval Center', 'Delegasi', 'Eksekusi'],
            'executive-dashboard' => ['Operasi', 'Keuangan', 'Approval Tertunda', 'KPI'],
            'fleet' => ['Equipment', 'Meter Log', 'Inspeksi', 'Maintenance'],
            'sales-order' => ['Price List', 'Sales Order', 'Reservasi Stok', 'Delivery Order'],
            'invoice' => ['Delivery Order', 'Invoice', 'PPN', 'Payment'],
            'payroll' => ['Attendance', 'Payroll Run', 'Jurnal Gaji', 'Payment'],
            'finance' => ['Kas', 'AR/AP', 'Jurnal', 'Laporan Keuangan'],
            'hr' => ['Karyawan', 'Attendance', 'Payroll'],
            'purchase-request' => ['PR', 'Approval', 'Komitmen Budget', 'PO'],
            'purchase-order' => ['PR', 'PO', 'GRN', 'Vendor Bill'],
            'attendance' => ['Shift', 'Check-in/out', 'Keterlambatan', 'Payroll'],
            'dispatch' => ['Dispatch', 'Hauling', 'Weighbridge', 'Rekonsiliasi'],
            'hauling' => ['Loading', 'Hauling', 'Dumping', 'Weighbridge'],
            'quality-control' => ['Sampling', 'Uji Lab', 'Hold Otomatis', 'CoA'],
            'cost-per-ton' => ['Fuel', 'Maintenance', 'Payroll', 'Output Tambang'],
            'hse' => ['Incident', 'Investigasi', 'Corrective Action', 'Close'],
            'heavy-equipment' => ['Equipment', 'Meter HM/KM', 'Fuel', 'Maintenance'],
            'reporting' => ['Filter Periode', 'Cetak/PDF', 'Export CSV', 'Audit Export'],
            'customer-deposit' => ['Deposit In', 'Alokasi Faktur', 'Refund', 'Statement'],
            'arap' => ['Invoice', 'Vendor Bill', 'Aging', 'Payment'],
            'audit-trail' => ['Aksi Transaksi', 'Log Immutable', 'Filter', 'Review'],
            'tire' => ['Pemasangan', 'Rotasi', 'Repair', 'Monitoring'],
            'operator-incentive' => ['Target', 'Insentif', 'Approval', 'Payroll'],
            'k3' => ['PPE Check', 'Toolbox Meeting', 'Inspeksi', 'Pelatihan'],
            'warehouse' => ['Penerimaan', 'Penyimpanan', 'Transfer', 'Opname'],
            'goods-receipt' => ['PO', 'GRN', 'QC Status', 'Stok Masuk'],
            'vendor-bill' => ['GRN', 'Vendor Bill', 'PPN Masukan', 'Hutang AP'],
            'cash-flow' => ['Receipt', 'Payment', 'Buku Kas', 'Laporan Arus Kas'],
            'fingerprint' => ['Mesin Fingerprint', 'Impor CSV', 'Keterlambatan', 'Payroll'],
            'tax' => ['PPN Keluaran', 'PPN Masukan', 'SPT', 'Pembayaran'],
            'compliance' => ['Registrasi', 'Reminder Harian', 'Renew', 'Kalender'],
            'laboratory' => ['Sampel', 'Uji Lab', 'Hasil', 'CoA'],
            'contract' => ['Kontrak Customer', 'Kontrak Supplier', 'Guard Over-Contract', 'Monitoring'],
            'document-management' => ['Surat Masuk/Keluar', 'Penomoran', 'Approval', 'Arsip'],
            'weighbridge-printer' => ['Tiket Timbang', 'Thermal 58/80mm', 'Reprint Teraudit', 'Workstation'],
            'general-ledger' => ['Jurnal', 'Posting', 'Buku Besar', 'Trial Balance'],
            'printer' => ['Device Manager', 'Mapping Dokumen', 'Test Print', 'Workstation'],
            'csr' => ['Proposal', 'Aktivitas', 'Beban Terjurnal', 'Dokumentasi'],
            'pwa' => ['Aplikasi Web', 'Install', 'Service Worker', 'Mode Offline'],
        ];
        foreach ($chains as $slug => $workflow) {
            SeoFeature::where('slug', $slug)->update(['workflow' => $workflow]);
        }
    }

    protected function industries(): void
    {
        $rows = [
            ['Pertambangan Batubara', 'Operasi pit, hauling, timbangan, stockpile, quality, dan penjualan dalam satu alur.', ['Mining', 'Hauling', 'Weighbridge', 'Stockpile', 'Quality', 'Sales', 'Accounting'], 10],
            ['Tambang Nikel', 'Kelola pit nikel, hauling ore, QC kadar, dan penjualan.', ['Mining', 'Hauling', 'Weighbridge', 'Quality', 'Sales', 'Accounting'], 10],
            ['Tambang Emas', 'Operasi tambang emas dari pit hingga pemurnian tercatat.', ['Mining', 'Weighbridge', 'Stockpile', 'Production', 'Sales', 'Accounting'], 20],
            ['Tambang Bauksit', 'Alur bauksit pit ke stockpile dan pengapalan.', ['Mining', 'Hauling', 'Weighbridge', 'Stockpile', 'Sales'], 30],
            ['Tambang Timah', 'Operasi timah dengan QC dan penjualan terdokumentasi.', ['Mining', 'Weighbridge', 'Quality', 'Sales', 'Accounting'], 40],
            ['Tambang Tembaga', 'Operasi tembaga multi-site terpusat.', ['Mining', 'Hauling', 'Weighbridge', 'Stockpile', 'Sales'], 40],
            ['Tambang Besi', 'Alur bijih besi pit hingga crusher dan penjualan.', ['Mining', 'Crusher', 'Stockpile', 'Weighbridge', 'Sales'], 50],
            ['Tambang Mangan', 'Operasi mangan terdokumentasi penuh.', ['Mining', 'Weighbridge', 'Stockpile', 'Sales'], 60],
            ['Tambang Pasir', 'Produksi pasir, timbangan, dan delivery tercatat.', ['Production', 'Weighbridge', 'Delivery Order', 'Invoice'], 50],
            ['Tambang Batu', 'Quarry batu dengan crusher dan penjualan.', ['Production', 'Crusher', 'Stockpile', 'Weighbridge', 'Sales'], 40],
            ['Quarry', 'Operasi quarry: produksi, crusher, timbangan, delivery.', ['Production', 'Crusher', 'Stockpile', 'Weighbridge', 'Delivery Order', 'Invoice'], 10],
            ['Stone Crusher', 'Plant crusher dengan batch, loss, dan stok produk.', ['Crusher', 'Production', 'Stockpile', 'Weighbridge', 'Sales'], 20],
            ['Mining Contractor', 'Kontraktor tambang: kontrak, equipment, dispatch, fuel, maintenance, cost, billing.', ['Contract', 'Fleet', 'Dispatch', 'Fuel', 'Maintenance', 'Cost per Ton', 'Invoice'], 10],
            ['Kontraktor Tambang', 'Kelola kontrak kerja tambang hingga penagihan.', ['Contract', 'Fleet', 'Dispatch', 'Fuel', 'Maintenance', 'Invoice'], 20],
            ['Hauling Contractor', 'Kontraktor angkutan: ritase, BBM, dan maintenance unit.', ['Dispatch', 'Hauling', 'Fuel', 'Maintenance', 'Weighbridge'], 30],
            ['Heavy Equipment Contractor', 'Kontraktor alat berat dengan kontrol HM dan biaya.', ['Fleet', 'Fuel', 'Tire', 'Maintenance', 'Cost per Ton'], 40],
            ['Mineral Processing', 'Pengolahan mineral dengan batch dan quality gate.', ['Production', 'Quality Control', 'Inventory', 'Sales'], 50],
            ['Smelter', 'Operasi smelter: input, produksi, dan penjualan.', ['Production', 'Inventory', 'Quality Control', 'Sales', 'Accounting'], 50],
            ['Port dan Jetty Mining', 'Operasi pelabuhan tambang: stockpile dan pengapalan.', ['Stockpile', 'Weighbridge', 'Delivery Order', 'Sales'], 60],
            ['Stockpile Operator', 'Operator stockpile: saldo, movement, dan survei.', ['Stockpile', 'Weighbridge', 'Inventory'], 60],
        ];

        foreach ($rows as [$name, $desc, $workflow, $priority]) {
            SeoIndustry::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'description' => $desc, 'workflow' => $workflow, 'priority' => $priority,
            ]);
        }
    }

    protected function locations(): void
    {
        $provinces = [
            'Indonesia' => [], 'Jakarta' => [], 'Jawa Barat' => ['Bandung', 'Bekasi', 'Bogor', 'Depok'],
            'Jawa Tengah' => ['Semarang', 'Surakarta'], 'Jawa Timur' => ['Surabaya', 'Gresik'],
            'Banten' => ['Tangerang'], 'Sumatera Selatan' => ['Palembang', 'Muara Enim'],
            'Sumatera Barat' => ['Padang'], 'Sumatera Utara' => ['Medan'],
            'Riau' => ['Pekanbaru'], 'Jambi' => ['Jambi'], 'Bengkulu' => ['Bengkulu'], 'Lampung' => ['Bandar Lampung'],
            'Kalimantan Timur' => ['Samarinda', 'Balikpapan', 'Bontang', 'Kutai Kartanegara', 'Kutai Timur', 'Kutai Barat', 'Berau', 'Paser', 'Penajam'],
            'Kalimantan Selatan' => ['Banjarmasin', 'Banjarbaru', 'Tapin', 'Tabalong', 'Tanah Bumbu', 'Kotabaru'],
            'Kalimantan Tengah' => ['Palangka Raya', 'Barito Utara', 'Murung Raya', 'Kapuas'],
            'Kalimantan Barat' => ['Pontianak', 'Ketapang', 'Sanggau', 'Sintang'],
            'Kalimantan Utara' => ['Tarakan', 'Bulungan'],
            'Sumatera Selatan' => ['Palembang', 'Muara Enim', 'Musi Banyuasin', 'Lahat'],
            'Jambi' => ['Jambi', 'Tanjung Jabung', 'Batanghari'],
            'Sumatera Barat' => ['Padang', 'Sijunjung', 'Dharmasraya'],
            'Riau' => ['Pekanbaru', 'Kuantan Singingi', 'Kampar', 'Rokan Hulu'],
            'Lampung' => ['Bandar Lampung', 'Way Kanan', 'Tulang Bawang'],
            'Sulawesi Tenggara' => ['Kendari', 'Morowali', 'Konawe', 'Kolaka', 'Bombana'],
            'Sulawesi Tengah' => ['Palu', 'Banggai', 'Morowali Utara'],
            'Sulawesi Selatan' => ['Makassar', 'Luwu Timur'],
            'Sulawesi Utara' => ['Manado', 'Minahasa', 'Bolaang Mongondow'],
            'Maluku Utara' => ['Ternate', 'Halmahera Tengah', 'Halmahera Timur'],
            'Papua' => ['Jayapura', 'Timika', 'Mimika', 'Nabire'],
            'Papua Barat' => ['Sorong'], 'Maluku' => ['Ambon'], 'Bengkulu' => ['Bengkulu'],
        ];

        $priority = 0;
        SeoLocation::updateOrCreate(['slug' => 'indonesia'], [
            'type' => 'country', 'name' => 'Indonesia', 'parent_id' => null, 'priority' => $priority++,
        ]);
        $indonesia = SeoLocation::where('slug', 'indonesia')->first();
        foreach ($provinces as $prov => $cities) {
            if ($prov === 'Indonesia') {
                continue;
            }
            $province = SeoLocation::updateOrCreate(['slug' => Str::slug($prov)], [
                'type' => 'province', 'name' => $prov, 'parent_id' => $indonesia->id, 'priority' => $priority++,
            ]);
            foreach ($cities as $city) {
                SeoLocation::updateOrCreate(['slug' => Str::slug($city)], [
                    'type' => 'city', 'name' => $city, 'parent_id' => $province->id, 'priority' => $priority++,
                ]);
            }
        }
    }

    protected function useCases(): void
    {
        $rows = [
            ['Weighbridge Tambang', 'Solusi timbangan truk tambang terintegrasi DO dan cetak tiket.', ['weighbridge', 'weighbridge-printer', 'delivery-order'], 10],
            ['Fuel Management Tambang', 'Kontrol BBM dari terima hingga issue per unit plus variance.', ['fuel', 'bbm', 'fleet', 'heavy-equipment'], 10],
            ['Maintenance Alat Berat', 'Jadwal, WO, sparepart, dan downtime alat berat.', ['maintenance', 'work-order', 'sparepart', 'tire'], 10],
            ['Fleet Management Mining', 'Armada, HM/KM, inspeksi, dan status unit.', ['fleet', 'heavy-equipment', 'dispatch'], 20],
            ['Stockpile Management', 'Saldo, movement, dan survei stockpile.', ['stockpile', 'inventory', 'weighbridge'], 20],
            ['Crusher Production', 'Batch crusher dengan loss dan stok produk.', ['crusher', 'production', 'stockpile'], 20],
            ['Dispatch Hauling Tambang', 'Dispatch ritase dan rekonsiliasi timbangan.', ['dispatch', 'hauling', 'weighbridge'], 20],
            ['Payroll Perusahaan Tambang', 'Penggajian multi-site dengan jurnal otomatis.', ['hr', 'attendance', 'payroll', 'operator-incentive'], 30],
            ['Accounting Perusahaan Pertambangan', 'Jurnal, TB/PL/BS, AR/AP, dan pajak.', ['accounting', 'finance', 'general-ledger', 'ar-ap', 'tax', 'cash-flow'], 20],
            ['Procurement Tambang', 'PR, PO, GRN, dan tagihan vendor terkendali budget.', ['procurement', 'purchase-request', 'purchase-order', 'goods-receipt', 'vendor-bill', 'budget'], 30],
            ['HSE dan Compliance Tambang', 'Insiden, permit kerja, aksi korektif, dan compliance.', ['hse', 'k3', 'compliance', 'approval'], 40],
            ['Executive Dashboard Tambang', 'Command center direksi multi-site real-time.', ['executive-dashboard', 'reporting', 'cost-per-ton'], 30],
        ];

        foreach ($rows as [$name, $desc, $slugs, $priority]) {
            SeoUseCase::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'description' => $desc, 'feature_slugs' => $slugs, 'priority' => $priority,
            ]);
        }
    }

    protected function keywords(): void
    {
        $rows = [
            ['source code erp mining', 'SOURCE_CODE', 'source', 100, 100], ['source code erp tambang', 'SOURCE_CODE', 'source', 100, 100],
            ['jual source code erp mining', 'BUY', 'source', 95, 100], ['jual source code erp tambang', 'BUY', 'source', 95, 100],
            ['beli source code erp mining', 'BUY', 'source', 90, 95], ['beli source code erp tambang', 'BUY', 'source', 90, 95],
            ['harga source code erp mining', 'PRICE', 'source', 95, 100], ['harga source code erp tambang', 'PRICE', 'source', 95, 100],
            ['source code aplikasi tambang', 'SOURCE_CODE', 'source', 80, 85], ['source code software pertambangan', 'SOURCE_CODE', 'source', 80, 85],
            ['source code sistem pertambangan', 'SOURCE_CODE', 'source', 75, 80], ['source code manajemen tambang', 'SOURCE_CODE', 'source', 75, 80],
            ['software mining', 'SOFTWARE', 'software', 85, 80], ['software tambang', 'SOFTWARE', 'software', 90, 85],
            ['software pertambangan', 'SOFTWARE', 'software', 90, 85], ['software perusahaan tambang', 'SOFTWARE', 'software', 80, 85],
            ['software manajemen tambang', 'SOFTWARE', 'software', 85, 85], ['software operasional tambang', 'SOFTWARE', 'software', 80, 80],
            ['software produksi tambang', 'SOFTWARE', 'software', 75, 75], ['software inventory tambang', 'SOFTWARE', 'software', 70, 75],
            ['software accounting tambang', 'SOFTWARE', 'software', 70, 75], ['software erp pertambangan', 'SOFTWARE', 'software', 90, 90],
            ['aplikasi tambang', 'APPLICATION', 'aplikasi', 90, 85], ['aplikasi pertambangan', 'APPLICATION', 'aplikasi', 90, 85],
            ['aplikasi mining', 'APPLICATION', 'aplikasi', 85, 80], ['aplikasi perusahaan tambang', 'APPLICATION', 'aplikasi', 80, 85],
            ['aplikasi operasional tambang', 'APPLICATION', 'aplikasi', 75, 75], ['aplikasi produksi tambang', 'APPLICATION', 'aplikasi', 70, 70],
            ['aplikasi stok tambang', 'APPLICATION', 'aplikasi', 65, 70], ['aplikasi timbangan tambang', 'APPLICATION', 'aplikasi', 70, 75],
            ['aplikasi fleet tambang', 'APPLICATION', 'aplikasi', 65, 70], ['aplikasi bbm tambang', 'APPLICATION', 'aplikasi', 65, 70],
            ['aplikasi maintenance tambang', 'APPLICATION', 'aplikasi', 65, 70],
            ['erp mining', 'SOFTWARE', 'erp', 95, 90], ['erp tambang', 'SOFTWARE', 'erp', 100, 95],
            ['erp pertambangan', 'SOFTWARE', 'erp', 95, 90], ['mining erp indonesia', 'SOFTWARE', 'erp', 85, 85],
            ['erp perusahaan tambang', 'SOFTWARE', 'erp', 85, 90], ['erp perusahaan pertambangan', 'SOFTWARE', 'erp', 80, 85],
            ['sistem erp tambang', 'SOFTWARE', 'erp', 85, 85], ['sistem erp pertambangan', 'SOFTWARE', 'erp', 80, 80],
            ['erp tambang terintegrasi', 'SOFTWARE', 'erp', 80, 85], ['erp mining management system', 'SOFTWARE', 'erp', 75, 80],
            ['software fuel management tambang', 'FEATURE', 'module', 80, 85], ['aplikasi bbm pertambangan', 'FEATURE', 'module', 75, 80],
            ['software fleet management mining', 'FEATURE', 'module', 80, 85], ['aplikasi maintenance alat berat tambang', 'FEATURE', 'module', 75, 80],
            ['software weighbridge pertambangan', 'FEATURE', 'module', 80, 85], ['aplikasi timbangan truk tambang', 'FEATURE', 'module', 70, 75],
            ['software stockpile management', 'FEATURE', 'module', 75, 80], ['software crusher production', 'FEATURE', 'module', 70, 75],
            ['software dispatch hauling tambang', 'FEATURE', 'module', 70, 75], ['aplikasi payroll perusahaan tambang', 'FEATURE', 'module', 70, 75],
            ['software accounting perusahaan pertambangan', 'FEATURE', 'module', 70, 75],
        ];

        foreach ($rows as [$keyword, $intent, $cluster, $priority, $commercial]) {
            SeoKeyword::updateOrCreate(['keyword' => $keyword], [
                'slug' => Str::slug($keyword), 'intent' => $intent, 'cluster' => $cluster,
                'priority' => $priority, 'commercial_score' => $commercial, 'indexable' => true,
            ]);
        }
    }
}
