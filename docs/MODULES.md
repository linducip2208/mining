# Modul & Fitur

## 1. Dashboard Eksekutif
Widget data nyata dari database: produksi hari ini/bulan, output crusher, tonase per site, penjualan, piutang, deposit, kas, revenue vs expense, karyawan aktif/hadir, WO terbuka, downtime, pending approval, stok kritis, top customer. Filter: site & periode. Chart: trend produksi, penjualan, tonase per site, stok.

## 2. Administrasi
- **User Management**: CRUD, aktivasi/nonaktif, reset password (temp + force reset), unlock, login history, logout all devices, assign roles
- **Role & Permission Matrix**: 25 role sistem, 658 permission granular (47 modul × 14 aksi), matriks UI per modul × aksi
- **Audit Trail**: filter modul/aksi/periode, IP + user agent, immutable
- **Settings**: konfigurasi inventaris, payroll, pajak, timbangan (semua tarif & perilaku configurable)

## 3. Organisasi
Company → Branch → Site (MINE/PLANT/PORT/OFFICE) → Division → Department, Cost Center. Semua master punya code/name/status/audit metadata + soft delete.

## 4. HR & Payroll
- Karyawan (NIP, posisi, tipe kepegawaian, shift, gaji pokok, bank)
- Absensi (manual + **import CSV fingerprint**: employee_code,date,check_in,check_out — late otomatis dari shift)
- Cuti & Lembur (dengan approval)
- **Insentif Operator**: basis TONNAGE/SHIFT/ACTIVITY/EQUIPMENT/TARGET, wajib APPROVED sebelum payroll
- **Payroll Run**: DRAFT → CALCULATE → APPROVE → POST (jurnal) → PAY
- Slip gaji cetak per karyawan

## 5. Operasi Tambang
Aktivitas harian per site/pit/shift/equipment/operator: DRAFT → SUBMITTED → APPROVED → POSTED. Posting memindahkan tonase material ke stockpile via Stock Ledger.

## 6. Timbangan (Weighbridge)
Tiket dua tahap: **timbang pertama** (First Weigh) → loading → **timbang kedua** → NET = GROSS − TARE → posting. Fitur: override berat (wajib alasan, ter-audit), void (approval workflow), kalibrasi + versi, cetak/reprint (reprint_count + audit), arah IN/OUT.

## 7. Produksi Crusher
Batch: input raw → output produk → loss (DEBU/MOISTURE/WASTE/PROCESS_LOSS/ADJUSTMENT) → scrap. **NET = GROSS − LOSS − SCRAP** dihitung otomatis. Posting: stok out raw, stok in FG (moving avg cost), jurnal Dr FG / Cr Raw.

## 8. Inventory
Multi warehouse/stockpile, kartu stok dengan saldo berjalan, transfer antar gudang, stock opname/penyesuaian (selisih dihitung dari ledger), reservasi SO, min stock/reorder point, nilai stok.

## 9. Procurement
PR → approval → PO → GRN (posting = stok masuk, QC status, **status PO otomatis PARTIALLY_RECEIVED/COMPLETED**) → Vendor Bill (Dr Inventory / Dr Admin Expense / Dr PPN Masukan / Cr AP — biaya inventory & admin **terpisah**) → pembayaran AP.

## 10. Penjualan
SO (DRAFT → Ajukan → APPROVED → Reservasi stok, harga auto dari price list berjenjang: customer → grup → site → standar) → DO (anti over-delivery) → timbangan → **complete DO** (stok keluar + update SO) → Faktur (dari qty terkirim termasuk SO COMPLETED tanpa faktur, PPN otomatis, jurnal Dr AR/Cr Revenue/Cr PPN, alokasi deposit opsional) → Pembayaran (FIFO ke faktur outstanding).

## 11. Harga & Margin
Price List per tipe (STANDARD/CUSTOMER/SITE/CONTRACT/RETAIL/SPECIAL) dengan effective/expiry, approval, price history. **Price Variance**: reference (ritel) vs realization, favorable/unfavorable, wajib approval — *informatif, makna akuntansi via mapping*.

## 12. Deposit Customer
Ledger: DEPOSIT_IN → DEPOSIT_USED → DEPOSIT_REFUND. Saldo dihitung dari ledger, anti overdraft. Statement per customer dengan saldo berjalan. Integrasi jurnal (Dr Cash / Cr Customer Deposit) + auto-alokasi ke faktur.

## 13. Aset & Maintenance
Asset register (status lifecycle), peralatan, jadwal pemeliharaan (interval jam/km/hari/bulan; **next_due otomatis untuk DAY/MONTH + generate WO draft anti-duplikat via tombol/`maintenance:generate-wo`**), Work Order (task, teknisi, sparepart) dengan flow DRAFT → APPROVED → IN_PROGRESS → COMPLETED. **Issue sparepart = stok keluar + biaya + jurnal Dr Maintenance Exp / Cr Inventory Sparepart**. Downtime tercatat.

## 14. Keuangan & Akuntansi
- Bagan akun standar pertambangan (30 akun) + 27 mapping configurable
- Jurnal manual (validasi balance) + jurnal otomatis dari semua transaksi
- Reversal jurnal (immutable, sekali)
- Laporan: **Neraca Saldo (balanced check), Buku Besar, Laba Rugi, Neraca, Arus Kas (berbasis buku kas), Umur Piutang, Umur Utang**
- Pajak: PPN keluaran/masukan otomatis dari faktur & vendor bill, PBB/SPT manual (tarif configurable)

## 15. Dokumen & CSR
- Surat masuk/keluar/internal/legal/permit dengan **penomoran otomatis** (001/DIVI/IX/2026), attachment (private storage), versi, kadaluarsa + reminder, approval, download log
- CSR: Proposal → Approval → Aktivitas → Beban (jurnal Dr CSR Exp / Cr Cash) → dokumentasi

## 16. Laporan
Mining (per site/shift/operator/peralatan + ton/jam), Produksi (batch + loss rekap), Inventory (saldo + kritis + nilai), Penjualan (per customer + produk + outstanding), HR (absensi + payroll), Maintenance (biaya + downtime + WO). Semua dengan filter periode + cetak/PDF + **export CSV** + audit export.

## 19. Konektivitas Antar Modul (Cross-Link UI + Audit Keterhubungan)

Setiap rantai bisnis dapat ditelusuri maju-mundur dari UI maupun database:

| Rantai | Tautan UI | Kunci Database |
|---|---|---|
| SO → DO → Timbang → Invoice → Payment | SO↔Faktur, DO↔SO, DO↔Tiket, Faktur↔SO | `qty_delivered`, `weighbridge_ticket_id`, `sales_order_id`, alokasi FIFO |
| Invoice → Jurnal → Sumber | Kolom Sumber di Jurnal link ke dokumen asal (`JournalEntry::sourceLink()`) | `source_type` + `source_id` |
| Deposit → Invoice → Sisa | Statement deposit; auto-alokasi + jurnal | ledger `DEPOSIT_USED` + jurnal `DEPOSIT_ALLOCATION` |
| PO → GRN → Bill → Bayar | Bill↔PO | `purchase_order_id`, `goods_receipt_id` |
| WO → Sparepart → Stok/Biaya | WO↔Equipment | ledger `MAINTENANCE_USAGE` + `maintenance_costs` |
| Mining → Stok → Produksi → Produk | Kartu stok per `ref_number` | ledger `ref_type` + `ref_id` |

Skrip `audit_link.php` (dijalankan saat verifikasi, tidak di-commit) memeriksa 12 titik konektivitas: semua OK, 0 orphan (faktur/payment/bill tanpa jurnal = 0, DO tanpa tiket = 0, approval tanpa action = 0, reservasi macet = 0).

## 17. Proactive Alerts (Scheduler)
Perintah `php artisan alert:scan` memindai 6 kondisi (konfigurable via `alert_rules`): stok minimum, faktur overdue, dokumen/permit kadaluarsa, maintenance jatuh tempo, selisih harga abnormal, approval tertunda >3 hari. Hasilnya menjadi notifikasi in-app untuk admin; dijadwalkan harian 07:00 via `routes/console.php`. Integrasi WhatsApp siap: isi `Settings → notification.whatsapp_webhook_url` dan alert otomatis terkirim ke webhook (`SystemAlert::toWhatsApp`).

## 18. Data Scope Enforcement
Filter `company_id`/`site_id` diterapkan otomatis di query index modul transaksional (mining, produksi, timbangan, procurement, sales, faktur, WO, payroll) berdasarkan scope peran user — diuji di `DataScopeTest`.
