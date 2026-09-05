# Deployment — Mining ERP

## 1. Prasyarat Server

- PHP 8.3+ dengan ekstensi: `pdo_mysql mbstring openssl bcmath gd intl zip fileinfo`
- Composer 2, Node.js 20+, MySQL 8.0+, web server (Apache/Nginx), HTTPS
- Opsional: Redis (cache/queue), Supervisor (queue worker), cron (scheduler)

## 2. Deploy Langkah-demi-Langkah

```bash
git clone https://github.com/linducip2208/mining.git miningerp
cd miningerp

composer install --no-dev --optimize-autoloader
npm install && npm run build

cp .env.example .env
php artisan key:generate --force
```

### 2.1 Konfigurasi `.env` produksi

```ini
APP_NAME="Mining ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.perusahaan-anda.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=miningerp_prod
DB_USERNAME=miningerp
DB_PASSWORD=<password-kuat>

SESSION_SECURE_COOKIE=true
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### 2.2 Database & seed struktural (AMAN di production)

```bash
php artisan migrate --force

# Struktural saja — idempoten, tanpa kredensial demo:
php artisan db:seed --class=CoreSeeder --force          # role + permission (superadmin DILEWATI di prod)
php artisan db:seed --class=AccountingSeeder --force    # COA + mapping + pajak + settings
php artisan db:seed --class=ApprovalWorkflowSeeder --force  # workflow approval (tanpa PR demo)
php artisan db:seed --class=AlertRuleSeeder --force     # aturan alert
```

> `UserSeeder`, `DemoSeeder`, `TransactionSeeder` **diblokir otomatis** di `APP_ENV=production` (ada guard di kode). Jangan dijalankan manual di prod.

### 2.3 Buat admin pertama (via tinker, password kuat, tidak tercatat di repo)

```bash
php artisan tinker
>>> $u = App\Models\User::create(['name'=>'Administrator','username'=>'admin','email'=>'admin@perusahaan-anda.id','password'=>bcrypt('<password-sangat-kuat>'),'status'=>'ACTIVE']);
>>> $u->roles()->attach(App\Models\Role::where('code','SUPER_ADMIN')->first()->id, ['scope'=>'ALL_COMPANIES']);
```

### 2.4 Cache & permission folder

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache
```

### 2.5 Virtual host (Apache contoh)

```apache
<VirtualHost *:80>
    ServerName erp.perusahaan-anda.id
    DocumentRoot "/var/www/miningerp/public"
    <Directory "/var/www/miningerp/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
Wajib HTTPS di production (Let's Encrypt). **DocumentRoot harus ke `public/`** — jangan ke root project (akan tampil daftar file).

## 3. Pasca Deploy (wajib)

1. Sesuaikan `accounting_mappings` & COA dengan struktur akun perusahaan.
2. Sesuaikan tarif `tax_codes` dengan regulasi terkini.
3. Buat user per peran (jangan pakai 1 akun bersama); nonaktifkan akun tak terpakai.
4. Aktifkan cron scheduler (alerts harian 07:00):
   `* * * * * php /var/www/miningerp/artisan schedule:run >> /dev/null 2>&1`
5. Jalankan queue worker via Supervisor (`queue:work`) untuk notifikasi.
6. Backup harian database + `storage/app/private` (lampiran dokumen).
7. Tutup periode fiskal lampau via menu Finance setelah closing.

## 4. Checklist Go-Live

- [ ] `APP_DEBUG=false`, APP_URL benar
- [ ] Tidak ada kredensial demo di DB (`superadmin`/`Demo!2345` tidak ada)
- [ ] Admin produksi dibuat manual
- [ ] COA/mapping/pajak disesuaikan
- [ ] HTTPS aktif, cookie secure
- [ ] Scheduler + queue worker jalan
- [ ] Backup terjadwal
- [ ] `php artisan test` hijau di staging sebelum naik prod
