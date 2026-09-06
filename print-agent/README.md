# Mining ERP Print Agent

Bridge lokal untuk Windows 10/11. Laravel tidak mengakses Bluetooth atau USB milik komputer pengguna; browser mengirim job yang sudah ditandatangani ke agent localhost, lalu agent menggunakan Windows spooler.

## Instalasi workstation

1. Install Node.js 18+.
2. Salin `config.example.json` menjadi `config.json`.
3. Isi `pairingToken` dengan token dari Pengaturan Sistem → Printer & Perangkat.
4. Isi `allowedOrigins` dengan origin HTTPS ERP.
5. Pair Bluetooth printer di Windows atau install printer USB/LAN seperti printer biasa.
6. Jalankan `npm start` dari folder ini. Agent hanya listen di `127.0.0.1`.

Endpoint yang tersedia: `GET /health`, signed `GET /printers`, signed `POST /print`, signed `POST /print/raw`, dan signed `POST /test`. Job UUID disimpan lokal untuk mencegah duplicate print dan job gagal tetap dapat dikirim ulang dari ERP.

Opsional, agar agent otomatis hidup saat login Windows, jalankan PowerShell `./install-startup.ps1`. Untuk membatalkan gunakan `./uninstall-startup.ps1`. Shortcut hanya dibuat di Startup profile pengguna saat ini.

Agent tidak menerima file path, executable, PowerShell command, atau nama printer yang belum terdeteksi oleh Windows. Untuk thermal, aplikasi mengirim template 58/80 mm; untuk A4/PDF gunakan Browser Print dan dialog system.
