<?php

namespace App\Docs\Content;

/**
 * Part6: panduan berbasis peran — rutinitas harian per user.
 */
class Part6
{
    public static function pages(): array
    {
        $roles = [
            'super-admin' => [
                'title' => 'Panduan Super Admin',
                'daily' => 'Kesehatan sistem, user & peran baru, audit trail, pengaturan, dan backup.',
                'links' => [
                    ['Manajemen Pengguna', '/docs/administration/users'],
                    ['Manajemen Peran', '/docs/administration/roles'],
                    ['Jejak Audit', '/docs/administration/audit'],
                    ['Dashboard Eksekutif', '/docs/getting-started/dashboard'],
                ],
                'keywords' => 'super admin administrator sistem user role audit',
            ],
            'site-manager' => [
                'title' => 'Panduan Site Manager',
                'daily' => 'Approval produksi & survei, downtime, HSE, dan laporan site.',
                'links' => [
                    ['Aktivitas Tambang', '/docs/mining/activity'],
                    ['Dashboard Armada', '/docs/fleet/dashboard'],
                    ['Laporan Insiden', '/docs/hse/reports'],
                    ['Pusat Persetujuan', '/docs/approval/center'],
                ],
                'keywords' => 'site manager kepala tambang approval produksi',
            ],
            'mine-manager' => [
                'title' => 'Panduan Mine Manager',
                'daily' => 'Rencana vs realisasi tambang, dispatch, stockpile, dan quality.',
                'links' => [
                    ['Dispatch Board', '/docs/dispatch/board'],
                    ['Stockpile Board', '/docs/stockpile/board'],
                    ['Sampel QC', '/docs/quality/samples'],
                    ['Laporan Produksi Tambang', '/docs/reports/mining'],
                ],
                'keywords' => 'mine manager tambang produksi dispatch stockpile',
            ],
            'weighbridge-operator' => [
                'title' => 'Panduan Operator Timbangan',
                'daily' => 'Timbang pertama/kedua, validasi tiket, cetak, dan void terkontrol.',
                'links' => [
                    ['Tiket Timbangan', '/docs/weighbridge/tickets'],
                    ['Perangkat Timbangan', '/docs/iot-weighbridge/devices'],
                    ['Trip Dispatch', '/docs/dispatch/trips'],
                ],
                'keywords' => 'operator timbangan weighbridge tiket jembatan',
            ],
            'warehouse' => [
                'title' => 'Panduan Gudang',
                'daily' => 'Penerimaan barang, mutasi, opname, dan saldo kritis.',
                'links' => [
                    ['Penerimaan BBM', '/docs/fuel/receipts'],
                    ['Dip Tangki', '/docs/fuel/dips'],
                    ['Kalender Compliance', '/docs/compliance/calendar'],
                ],
                'keywords' => 'gudang warehouse stok penerimaan opname',
            ],
            'sales' => [
                'title' => 'Panduan Sales',
                'daily' => 'SO, surat jalan, faktur, deposit, dan realisasi kontrak.',
                'links' => [
                    ['Kontrak Customer', '/docs/contract/customers'],
                    ['Realisasi Kontrak', '/docs/contract/realization'],
                    ['Sampel QC', '/docs/quality/samples'],
                ],
                'keywords' => 'sales penjualan kontrak faktur surat jalan',
            ],
            'finance' => [
                'title' => 'Panduan Finance',
                'daily' => 'Jurnal, tutup periode, budget vs aktual, AR/AP, dan pajak.',
                'links' => [
                    ['Jurnal', '/docs/finance/journals'],
                    ['Budget vs Aktual', '/docs/budget/vs-actual'],
                    ['Biaya per Ton', '/docs/cost/dashboard'],
                ],
                'keywords' => 'finance keuangan akuntansi jurnal budget kas',
            ],
            'hr' => [
                'title' => 'Panduan HR',
                'daily' => 'Karyawan, absensi, lembur, insentif, dan payroll.',
                'links' => [
                    ['Navigasi Aplikasi', '/docs/getting-started/navigasi'],
                    ['Kalender Compliance', '/docs/compliance/calendar'],
                ],
                'keywords' => 'hr sdm karyawan absensi payroll lembur',
            ],
            'maintenance' => [
                'title' => 'Panduan Maintenance',
                'daily' => 'Work order, jadwal, downtime, dan biaya alat.',
                'links' => [
                    ['Inspeksi Unit', '/docs/fleet/inspections'],
                    ['HM Odometer', '/docs/fleet/meters'],
                    ['Ban', '/docs/tire/index'],
                ],
                'keywords' => 'maintenance perawatan work order mekanik downtime',
            ],
            'hse' => [
                'title' => 'Panduan HSE',
                'daily' => 'Laporan bahaya/insiden, corrective action, permit, dan toolbox.',
                'links' => [
                    ['Dashboard HSE', '/docs/hse/dashboard'],
                    ['Laporan Insiden', '/docs/hse/reports'],
                    ['Permit Kerja', '/docs/hse/permits'],
                ],
                'keywords' => 'hse k3 safety insiden permit bahaya',
            ],
            'auditor' => [
                'title' => 'Panduan Auditor',
                'daily' => 'Jejak audit, reversal jurnal, override, dan laporan baca-saja.',
                'links' => [
                    ['Jejak Audit', '/docs/administration/audit'],
                    ['Jurnal', '/docs/finance/journals'],
                    ['Budget vs Aktual', '/docs/budget/vs-actual'],
                ],
                'keywords' => 'auditor audit jejak pemeriksaan',
            ],
        ];

        $pages = [];
        foreach ($roles as $slug => $r) {
            $pages[$slug] = [
                'title' => $r['title'],
                'module' => 'Panduan Peran',
                'permission' => 'Sesuai modul masing-masing',
                'nav' => 'Dokumentasi → Panduan Peran',
                'purpose' => 'Rutinitas harian ' . strtolower($r['title']) . ': ' . $r['daily'],
                'steps' => [
                    'Login dan cek Dashboard Eksekutif + badge persetujuan/notifikasi.',
                    'Kerjakan daftar di bawah sesuai urutan prioritas.',
                    'Tutup hari dengan memastikan tidak ada status menggantung (DRAFT/SUBMITTED).',
                ],
                'fields' => [],
                'buttons' => [],
                'workflow' => 'Dashboard → modul tugas → approval center → selesai.',
                'expected' => 'Semua tugas peran hari itu berstatus final (POSTED/COMPLETED/CLOSED).',
                'errors' => ['Menu tidak terlihat' => 'Peran tidak punya izin modul itu; hubungi admin.'],
                'tips' => ['Gunakan Ctrl+K untuk lompat antar halaman tugas.'],
                'related' => $r['links'],
                'keywords' => $r['keywords'],
            ];
        }

        return [
            'roles' => ['title' => 'Panduan Peran', 'pages' => $pages],
            'analytics' => ['title' => 'AI & Analitik', 'pages' => [
                'forecast' => [
                    'title' => 'Forecast & Deteksi Anomali',
                    'module' => 'Analitik',
                    'permission' => 'forecast.view',
                    'nav' => 'Laporan → Forecast & Anomali',
                    'purpose' => 'Proyeksi produksi dan arus kas (moving average) plus deteksi anomali BBM, timbangan, harga beli, lembur, downtime, dan penyesuaian stok (z-score > 2,5 sigma).',
                    'steps' => ['Buka Laporan → Forecast & Anomali.', 'Baca kartu forecast produksi 7 hari dan kas 14 hari.', 'Telusuri kartu anomali; klik angka untuk dokumen sumber.'],
                    'fields' => [],
                    'buttons' => [],
                    'workflow' => 'Agregat 30/90 hari → moving average + z-score → kartu anomali.',
                    'expected' => 'Penyimpangan operasional terlihat sebelum menjadi kerugian.',
                    'errors' => ['Semua nol' => 'Belum ada transaksi 30 hari terakhir.'],
                    'tips' => ['Anomali BBM berulang pada satu unit = jadwalkan inspeksi.'],
                    'related' => [['Dashboard BBM', '/docs/fuel/dashboard'], ['Laporan Produksi Tambang', '/docs/reports/mining']],
                    'keywords' => 'forecast anomali proyeksi moving average z-score',
                ],
                'ai-copilot' => [
                    'title' => 'AI Copilot',
                    'module' => 'AI',
                    'permission' => 'ai.view',
                    'nav' => 'Laporan → AI Copilot',
                    'purpose' => 'Tanya jawab operasional berbasis data real (read-only, tercatat di audit). Provider lokal bawaan atau LLM eksternal via pengaturan.',
                    'steps' => ['Buka AI Copilot.', 'Ketik pertanyaan (mis. stok kritis apa saja?).', 'Baca jawaban + sumber datanya.', 'Riwayat tersimpan per pengguna.'],
                    'fields' => [],
                    'buttons' => [],
                    'workflow' => 'Pertanyaan → konteks agregat read-only → jawaban + audit log.',
                    'expected' => 'Jawaban angka yang bisa diverifikasi ke modul sumber.',
                    'errors' => ['Provider belum dikonfigurasi' => 'Gunakan LOCAL atau isi kredensial di Pengaturan Sistem.'],
                    'tips' => ['AI tidak pernah mengubah data — aman untuk eksplorasi.'],
                    'related' => [['Dashboard Eksekutif', '/docs/getting-started/dashboard']],
                    'keywords' => 'ai copilot tanya jawab asisten llm',
                ],
                'executive' => [
                    'title' => 'Executive Dashboard',
                    'module' => 'Laporan',
                    'permission' => 'executive.view',
                    'nav' => 'Laporan → Executive Dashboard',
                    'purpose' => 'Satu layar command center: produksi, revenue, biaya, margin, Rp/ton, BBM, armada, HSE, piutang/utang, dan approval tertunda — semua drill-down.',
                    'steps' => ['Buka Executive Dashboard.', 'Atur mode periode (hari ini/kemarin/MTD/YTD/kustom).', 'Filter company/site/pit/produk.', 'Klik kartu untuk drill-down ke modul.'],
                    'fields' => ['Mode' => 'Rentang cepat tanpa isi tanggal manual.', 'Filter' => 'Company, site, pit, produk.'],
                    'buttons' => [],
                    'workflow' => 'Agregat seluruh modul → KPI → drill-down.',
                    'expected' => 'Kondisi perusahaan terkini dalam satu layar.',
                    'errors' => [],
                    'tips' => ['Jadikan halaman awal direksi setiap pagi.'],
                    'related' => [['Dashboard Eksekutif', '/docs/getting-started/dashboard'], ['Biaya per Ton', '/docs/cost/dashboard']],
                    'keywords' => 'executive dashboard kpi command center direksi',
                ],
            ]],
        ];
    }
}
