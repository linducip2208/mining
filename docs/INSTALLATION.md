# Instalasi & Deployment

## Prasyarat
- PHP 8.3+ (ekstensi: pdo_mysql, mbstring, openssl, bcmath, gd, intl, zip, fileinfo)
- Composer 2
- MySQL 8.0+ / 8.4
- Node.js 20+ (build asset)
- (Opsional) Redis untuk queue/cache produksi

## Instalasi Development

```bash
git clone <repo> miningerp && cd miningerp

composer install
npm install

cp .env.example .env
php artisan key:generate

# .env
# APP_NAME="Mining ERP"
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=miningerp
# DB_USERNAME=root
# DB_PASSWORD=

mysql -u root -e "CREATE DATABASE IF NOT EXISTS miningerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

php artisan migrate

# Seed WAJIB berurutan:
php artisan db:seed --class=CoreSeeder          # role, permission, super admin
php artisan db:seed --class=AccountingSeeder    # COA, mapping, pajak, settings
php artisan db:seed --class=DemoSeeder          # master demo (2 company, 100 karyawan, dll)
php artisan db:seed --class=TransactionSeeder   # transaksi demo end-to-end
php artisan db:seed --class=AlertRuleSeeder     # 6 aturan alert proaktif
php artisan db:seed --class=UserSeeder          # 20 user demo lintas peran & scope
php artisan db:seed --class=ApprovalWorkflowSeeder  # 6 workflow approval + 1 PR demo SUBMITTED

npm run build
php artisan serve
```

## Testing

```bash
php artisan test
# 18 tests, 35 assertions — PASS
```

## Deployment Produksi

```bash
composer install --no-dev --optimize-autoloader
npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache

# scheduler & queue (opsional tapi disarankan)
# crontab: * * * * * php /path/artisan schedule:run >> /dev/null 2>&1
# queue:   php artisan queue:work (supervisor)
```

### Checklist Go-Live
1. **Ganti password super admin** (seed hanya untuk demo) — atau buat admin baru lalu nonaktifkan akun seed.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` benar.
3. Konfigurasi COA & `accounting_mappings` sesuai struktur akun perusahaan (bisa diubah tanpa deploy ulang).
4. Sesuaikan tarif pajak di `tax_codes` (PPN 11/12% dsb) sesuai regulasi terkini.
5. Buat `FiscalPeriod` per tahun berjalan (status OPEN) — seeder membuat 2 tahun.
6. Aktifkan HTTPS, session cookie `secure`, dan backup database harian.
7. Nonaktifkan registrasi mandiri (sudah tidak ada route register).

## Struktur Kunci

```
app/
├── Http/Controllers/       # 40+ controller (master + transaksional)
├── Http/Middleware/        # CheckPermission (RBAC backend)
├── Models/                 # 110+ model (BaseModel dengan auto date-cast)
├── Services/               # 11 service business logic inti
└── Notifications/
database/
├── migrations/             # 12 file migration (124 tabel)
└── seeders/                # Core, Accounting, Demo, Transaction
resources/views/            # 145+ blade (layout enterprise + modul)
tests/Feature/              # CriticalFlowTest + PasswordChangeTest
docs/                       # Dokumentasi lengkap
```
