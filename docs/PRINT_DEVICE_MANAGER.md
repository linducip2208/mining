# Enterprise Print Device Manager

## Arsitektur

Mining ERP memakai dua jalur cetak. Laporan, invoice, PO, financial report, dan PDF memakai dedicated print view lalu Browser/System Printer. Tiket timbang, fuel receipt, dan struk thermal memakai browser sebagai pengirim job ke **Mining ERP Print Agent** yang berjalan di workstation pengguna.

Laravel tidak mencoba mengakses Bluetooth atau USB di komputer pengguna. Agent hanya listen pada `127.0.0.1`, memakai Windows spooler, dan mendukung printer yang sudah dipair/install sebagai printer Windows: Bluetooth, USB, LAN, thermal 58/80 mm, ESC/POS-compatible, dan A4 system printer.

## Setup workstation Windows 10/11

1. Pastikan Node.js 18+ tersedia.
2. Salin `print-agent/config.example.json` menjadi `print-agent/config.json`.
3. Buat token pairing dari Pengaturan Sistem → Printer & Perangkat, lalu isi `pairingToken`.
4. Isi `allowedOrigins` dengan origin HTTPS aplikasi.
5. Pair Bluetooth atau install printer USB/LAN melalui Windows Settings.
6. Dari folder `print-agent`, jalankan `npm start`.
7. Pada ERP, buka Pengaturan Sistem → Printer & Perangkat → Deteksi Printer.
8. Simpan printer dengan nama Windows, tipe kertas, scope site/workstation, dan jenis dokumennya.

## API agent lokal

| Endpoint | Fungsi |
|---|---|
| `GET /health` | Health check non-sensitif |
| `GET /printers` | Discovery printer Windows, signed |
| `POST /print` | Print HTML/text melalui spooler, signed |
| `POST /print/raw` | Payload raw terbatas untuk ESC/POS, signed |
| `POST /test` | Test print, signed |

Setiap request selain health membutuhkan timestamp dan HMAC SHA-256 atas `timestamp.METHOD.body`. Agent menolak request non-loopback, origin yang tidak diizinkan, printer yang belum terdeteksi, file path, executable, dan command dari request. Job UUID disimpan di `print-queue.json`, sehingga retry dengan UUID yang sudah tercetak tidak menggandakan hasil.

## Routing dan fallback

Routing mencari kecocokan jenis dokumen, workstation, site, dan company. Jika agent offline, transaksi tetap tersimpan dan print job menjadi `FAILED`; pengguna dapat mencoba lagi, memilih printer, mencetak dedicated browser view, atau mengunduh PDF. Auto print tiket timbang berjalan setelah timbang kedua berhasil, bukan menjadi syarat commit transaksi.

Status job: `QUEUED`, `SENDING`, `PRINTED`, `FAILED`, `CANCELLED`. Test print, retry, default printer, dan cetak ulang dicatat ke Audit Trail. Nilai pairing token tidak pernah ditampilkan atau dicatat sebagai raw value.

## Hak akses

`printer.view` melihat registry, `printer.manage` mengelola device dan retry, `printer.test` menjalankan test print, `weighbridge.reprint` mencetak ulang tiket timbang, dan `document.reprint` disiapkan untuk dokumen transaksi. A4 tetap membutuhkan permission print/PDF dokumen terkait.
