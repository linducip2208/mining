# Test Report — Mining ERP

Tanggal: 2026-09-07 · Runner: `php artisan test` (PHPUnit 12, SQLite in-memory) + smoke HTTP + ledger check.

## Ringkasan

| Metrik | Hasil |
|---|---|
| Total tests | 195 |
| Passed | 195 |
| Failed | 0 |
| Assertions | 3038 |
| Playwright responsive (17 halaman × 8 viewport) | 136/136 PASS |
| Trial Balance (data demo) | BALANCED |
| Stok negatif | 0 baris |

## Cakupan per File Test

### CriticalFlowTest (16 test)
Auth (login username, guest redirect), RBAC (403 tanpa permission, 200 dengan permission), privilege escalation (assign role butuh `role.update`, self-deactivate/self-elevate 403), accounting (unbalanced ditolak, posting, reversal, anti double-reverse), inventory (negative stock ditolak, in/out balance), numbering unik, deposit (in/use/refund/overdraft), weighbridge NET, unauthorized approver ditolak.

### DataScopeTest (5 test)
Filter site & company pada index, **IDOR 403 pada URL record langsung** (mining, SO), create lintas scope 403.

### ApprovalEngineTest (5 test)
Submit → assign approver, unauthorized ditolak, approve/reject menerapkan status, delegasi dapat bertindak.

### PasswordChangeTest (3 test)
Ganti password OK, password lama salah ditolak, user LOCKED tidak bisa login.

### FlowIntegrationTest (7 test) — wiring antar modul
GRN post → PO PARTIALLY_RECEIVED → COMPLETED; PR via Center → komitmen budget; overtime approve + index (relasi approver); SO submit → approve → reserve (reservasi tercatat); invoice create menawarkan SO COMPLETED tanpa faktur; jadwal → next_due + generate WO sekali; `Setting::get` tahan tanpa tabel.

### PwaTest (4 test)
Manifest valid + ikon default + display standalone saat enabled; halaman offline publik; service worker & ikon ada di disk.

### SEO suite (25 test: 22 aspek + admin + helper coverage)
Page/slug/canonical/metadata/schema/sitemap(+index)/quality/duplicate/internal-link/orphan/WhatsApp/price/feature-claim/location-claim/noindex/idempotency/limit/hash/robots/breadcrumb/responsive + admin dashboard/pages/publish-noindex-archive + error-page resilience (404/403 tanpa tabel settings).

### EndToEndTest (5 test) — §29 TEST A–E
- **TEST A (Mine→Cash)**: raw 1000 → batch (800 in/650 net) → DO net 495 → invoice 109,89jt (deposit 50jt teralokasi + jurnal) → lunasi → PAID; verifikasi stok (200/155), revenue −99jt, AR 0, deposit 0, jurnal alokasi ada, balance global.
- **TEST B (Procure→Pay)**: PO → GRN → stok 10 → **over-receipt via HTTP ditolak** → bill → jurnal 3 baris benar → bayar lunas → AP 0 → overpay ditolak → balance global.
- **TEST C (Payroll→GL)**: kalkulasi (gross 19jt/potongan 500rb/neto 18,5jt) → post → jurnal benar → double-post ditolak → bayar → salary payable 0 → balance global.
- **TEST D (Maintenance)**: issue part → stok 10→8 + cost 1jt + jurnal → issue ganda ditolak → balance global.
- **TEST E (Deposit)**: in 5jt + jurnal → overdraft ditolak → pakai 2jt → refund 1jt → saldo 2jt → balance global.

## Verifikasi Manual Tambahan (dilaksanakan, bukan sekadar klaim)

- Smoke 39 endpoint via HTTP sebagai superadmin: semua 200.
- Live IDOR: user sales → `/users` = 403; SO list 200 (scope-nya).
- Approval E2E via browser: purchasing submit → site_mgr approve → PR APPROVED (sesi sebelumnya).
- `php artisan alert:scan`: 15 stok kritis → notifikasi terkirim.
- `npm run build`: sukses.
- `migrate:fresh` + 7 seeder: sukses berurutan.
