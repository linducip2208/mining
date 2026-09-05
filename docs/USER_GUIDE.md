# Panduan Pengguna

## Login
Buka `/login` → masukkan **username atau email** + password.

Super admin demo: `superadmin` / `Admin!2345` (local saja).

## Navigasi
- Sidebar kiri: menu bergrup (hanya menampilkan modul yang Anda boleh akses)
- Topbar: global search (invoice, DO, PO, tiket timbang, customer, dll), ikon persetujuan (badge jumlah pending), notifikasi, profil

## Alur Harian Operasional

### 1. Catat Produksi Tambang
Operasi Tambang → Catat Aktivitas → isi site/pit/shift/alat/operator/material/tonase → **Simpan** → **Ajukan** → Approver menyetujui → **Posting ke Stok** (tonase masuk stockpile).

### 2. Timbangan
Timbangan → **Timbang Pertama** (nopol + berat) → loading → buka tiket → isi customer/item → **Simpan Timbang Kedua** → NET muncul → **Cetak Tiket** / **Posting**. Untuk penjualan: selesaikan DO dengan tiket ini.

### 3. Produksi Crusher
Produksi → Batch Baru → input material + output produk + loss → Simpan → Ajukan → Setujui → **Posting** (stok raw keluar, produk masuk, jurnal otomatis).

### 4. Penjualan Sampai Pembayaran
1. Penjualan → Sales Order → Baru → customer + item + qty (harga auto) → Simpan → Setujui
2. Delivery Order → Baru → pilih SO + gudang + nopol → Simpan
3. Lakukan timbang keluar (modul timbangan)
4. DO → **Selesaikan + Timbang** → masukkan no tiket → stok keluar & SO ter-update
5. Faktur → Dari SO → pilih SO → **Buat & Posting Faktur** (jurnal otomatis; centang "gunakan deposit" bila customer deposit)
6. Pembayaran → Terima Pembayaran → alokasi otomatis FIFO

### 5. Pembelian
PR (Buat → Ajukan → Setujui) → PO (Buat dari PR → Setujui) → GRN (Buat dari PO → **Posting**, stok masuk) → Tagihan Vendor (Buat → **Posting**, jurnal AP) → **Bayar**.

### 6. Deposit Customer
Penjualan → Deposit Customer → form **Terima Deposit** (kas/bank wajib) atau **Refund**. Lihat saldo per customer & **Statement** (ledger berjalan). Saat buat faktur, centang gunakan deposit untuk pelunasan otomatis.

### 7. Payroll
HR → Payroll → Run Baru (perusahaan + periode) → **Kalkulasi** (basic + tunjangan + lembur + insentif approved − potongan − PPh21) → **Setujui** → **Posting Jurnal** → **Tandai Dibayar**. Slip gaji per karyawan via tombol "Slip".

### 8. Maintenance
Work Order → Baru (alat, sparepart, teknisi) → Setujui → Mulai → **Issue Sparepart** (stok keluar + biaya + jurnal) → Selesaikan (isi downtime + biaya tenaga kerja).

### 9. Persetujuan (Approver)
Ikon perisai di topbar → daftar permintaan menunggu → **Setujui / Tolak** (tolak wajib alasan). Semua tindakan ter-audit.

### 10. Laporan
Grup Laporan → pilih laporan → filter periode → **Cetak / PDF** (tombol print browser). Export CSV tersedia di modul master (tombol filter `?export=1`).

## Tips
- Semua nomor dokumen dibuat otomatis (SO-202609--000001 dst).
- Error "Jurnal tidak balance" / "Stok tidak cukup" = validasi sistem, bukan bug — perbaiki input.
- Gunakan Reset pada baris filter untuk menghapus filter.
- Override berat timbangan & void tiket selalu meninggalkan jejak audit.
