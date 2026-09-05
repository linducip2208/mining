# Mining ERP — Integrated Mining Operations & Enterprise Resource Planning

Sistem ERP terintegrasi untuk operasi pertambangan: dari tambang → timbangan → produksi → inventory → penjualan → keuangan, dengan HR, Procurement, Maintenance, Document, CSR, RBAC, Approval Engine, dan Audit Trail yang menyatu.

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Database | MySQL 8.4 (InnoDB, FK, DECIMAL) |
| Frontend | Blade + Tailwind CSS 4 + Alpine.js |
| Chart | Chart.js 4 |
| Auth | Laravel Breeze (login username/email) |
| Testing | PHPUnit 12 |

## Instalasi Cepat (Lokal)

```bash
# 1. Install dependency
composer install
npm install && npm run build

# 2. Konfigurasi .env
cp .env.example .env
php artisan key:generate
# Atur: DB_CONNECTION=mysql, DB_DATABASE=miningerp, DB_USERNAME=root, DB_PASSWORD=

# 3. Buat database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS miningerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 4. Migrasi + seed (urutan penting)
php artisan migrate
php artisan db:seed --class=CoreSeeder
php artisan db:seed --class=AccountingSeeder
php artisan db:seed --class=DemoSeeder
php artisan db:seed --class=TransactionSeeder
php artisan db:seed --class=AlertRuleSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=ApprovalWorkflowSeeder

# 5. Jalankan
php artisan serve

# 6. Scheduler alerts (opsional, cron produksi)
* * * * * php /path/artisan schedule:run >> /dev/null 2>&1
# atau manual: php artisan alert:scan [--dry]
```

### Kredensial Demo (HANYA untuk local/demo — password user: `Demo!2345`)

```
URL      : http://miningerp.test/login   (Laragon vhost → folder project/public)
Username : superadmin          (Super Admin, full access)
Password : Admin!2345
```

| Username | Peran | Scope |
|---|---|---|
| director / gm | Direktur / GM | Semua perusahaan |
| mine_mgr / prod_mgr / fin_mgr | Manager | Perusahaan MIN1 |
| site_mgr | Site Manager | Site S-BJM |
| purchasing / accounting / hr_mgr | Staff | Perusahaan MIN1 |
| wh_mgr | Warehouse Manager | Site S-KTN |
| sales_mgr / sales | Sales | MIN1 / Site S-BJM |
| wb_opt | Operator Timbangan | Site S-BJM |
| mnt_mgr / doc_ctrl / csr | Pendukung | Bervariasi |
| auditor / viewer | Read-only | Semua |
| min2_mgr / min2_sales | Site & Sales | Site S-SMD (MIN2) |

> Password super admin ditanam hanya pada `CoreSeeder` untuk lingkungan demo. Ganti segera saat deploy.

**Coba alur approval**: login sebagai `purchasing` → buat PR → `site_mgr` mendapat notifikasi persetujuan di topbar. Data demo sudah berisi 1 PR berstatus SUBMITTED (menunggu `site_mgr`).

## Struktur Dokumentasi

- [Arsitektur Sistem](ARCHITECTURE.md)
- [Modul & Fitur](MODULES.md)
- [Skema Database](DATABASE.md)
- [RBAC & Data Scope](RBAC.md)
- [Workflow Status & Approval](WORKFLOW.md)
- [Akuntansi & Mapping](ACCOUNTING.md)
- [Panduan Pengguna](USER_GUIDE.md)

## Prinsip Desain

```
SATU DATA · SATU PROSES · SATU SISTEM · SATU AUDIT TRAIL · SATU SUMBER KEBENARAN
```

- **Stock**: source of truth = `stock_ledger` (append-only), saldo = SUM(in) − SUM(out). Tidak ada update angka langsung.
- **Deposit**: source of truth = `customer_deposits` (ledger). Saldo dihitung dari ledger.
- **Akuntansi**: double-entry wajib; jurnal tidak balance tidak bisa POST; koreksi hanya via reversal.
- **Numbering**: concurrency-safe dengan row lock + period reset.
- **Authorization**: dicek di backend (middleware `permission`), bukan hanya menyembunyikan menu.
