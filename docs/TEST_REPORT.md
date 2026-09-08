# Test Report — Mining ERP

Tanggal: 2026-09-08 · Runner: `php artisan test` (PHPUnit 12, SQLite in-memory).

## Ringkasan

| Metrik | Hasil |
|---|---|
| Total tests | 424 |
| Passed | 424 |
| Failed | 0 |
| Assertions | 3.742 |
| `npm run build` | PASS |
| `migrate:fresh --seed` | PASS |
| `inventory:audit-integrity` | PASS (exit 0) |
| `accounting:audit-integrity` | PASS (exit 0, 1 warn toleransi opening) |
| `seo:audit` | PASS — indexable 1.000, orphan 0, broken link 0 |
| Playwright responsive (17 halaman × 8 viewport) | 136/136 PASS |
| Trial Balance (data demo) | BALANCED |
| Stok negatif | 0 baris |

## Cakupan per File Test

### EndToEndTest (5) — mine-to-cash A–E
TEST A raw→produksi→DO 495t→invoice→deposit→lunas; TEST B PO→GRN→bill→bayar (over-receipt & overpay ditolak); TEST C payroll kalkulasi→post→bayar (double-post ditolak); TEST D issue part→jurnal→double-issue ditolak; TEST E deposit in/use/refund/overdraft. Semua diverifikasi saldo stok, AR/AP 0, jurnal balance.

### SalesCogsTest (4) — COGS/HPP
avg_cost=0 + policy BLOCK → delivery ditolak; policy WARN → jalan + audit WARNING, tanpa jurnal nol; avg_cost>0 → Dr COGS = net × moving-avg (tepat 50.000); invoice idempoten per SO.

### PayrollOvertimeIntegrationTest (4) — anti double-count
OVERTIME_REQUEST_ONLY mengabaikan attendance minutes; ATTENDANCE_ONLY sebaliknya; MERGED_NON_DUPLICATE memakai max per tanggal (bukan jumlah: 1h+3h = 3, bukan 4); lembur REJECTED tidak dibayar.

### PayrollAccountingTest (5) — split liability
PPh21 → hutang PPh21; LOAN → LOAN_RECEIVABLE (tepat 1.000.000, PPh21 tidak tercemar); potongan lain → OTHER_PAYROLL_PAYABLE; jurnal tetap balance dengan potongan campuran; pay() melunasi SALARY_PAYABLE.

### SparepartWorkOrderEndToEndTest (3) — reserve→issue→return→consumed
reserve menurunkan available; issue memirror issued_qty + jurnal Dr Maint Exp / Cr Inv 200.000; return pakai **harga issue asli** (50.000, bukan avg baru 75.000) + consumed_qty = issued − returned; over-issue ditolak; reservasi konkuren tidak bisa over-allocate.

### ReceiptPaymentLinkTest (4) — receipt↔payment↔invoice
kwitansi dari payment POSTED = evidence only (tanpa jurnal kedua); kwitansi ganda per payment ditolak; 1 payment → 2 alokasi invoice (total alokasi ≤ payment); invoice 2 pembayaran parsial → PAID, outstanding = total − alokasi, overpay ditolak.

### ReceiptNumberConcurrencyTest (3) — penomoran kwitansi
100 nomor → 100 unik, seq counter tepat 100, format token benar; reset bulanan kembali ke 000001 (nomor void tidak dipakai ulang); counter spesifik company menang atas counter global.

### ExcelImportTest (11) — native spreadsheet
xlsx dibaca native (headers/rows benar); macro-enabled ditolak; multi-sheet pilih by name; auto-map alias (NO. SURAT → number, KODE BARANG → code); parser angka Indonesia (Rp 12.000.000 / 2.000 / 2,5 / (1.500)); tanggal + WARNING ambigu + serial Excel; file hash duplikat peringatan; row fingerprint lintas batch; dry-run tanpa write; OPENING_AR posting jurnal, REGISTER_ONLY tidak.

### LetterNumberConcurrencyTest
Reservasi nomor surat konkuren (lock atomic) — 0 duplikat.

### CriticalFlowTest (16), DataScopeTest (5), ApprovalEngineTest (5)
Auth/RBAC/IDOR 403, unbalanced journal ditolak, reversal anti double, negative stock ditolak, numbering unik, deposit overdraft ditolak, unauthorized approver ditolak.

### FlowIntegrationTest (7), ModuleFlowTest, ModuleIntegrationTest
Wiring antar modul: GRN→PO rollup, PR→budget commit, SO submit→approve→reserve, invoice menawarkan SO completed tanpa faktur, schedule→WO generate sekali, HOLD QC memblokir DO.

### Sparepart suite (11), Stock suite (4)
Master/receipt/issue/return/reservation/low-stock/rekomendasi PR/permission/opname (COUNTING→REVIEW→posting jurnal selisih).

### SEO suite (25) + admin
Price Rp12 Juta konsisten, WhatsApp 6281296052010 ternormalisasi, orphan 0, canonical, noindex FAIL→demote, sitemap index, robots, breadcrumb, schema tanpa fake rating, idempotency generator, tier cap.

### Payroll/PWA/Docs/Settings/Print suite
Slip gaji, manifest PWA + ikon, docs routing/konteks, settings label manusia, print branding tanpa hardcode.

## Verifikasi Manual Tambahan

- `php artisan inventory:audit-integrity` → PASS (21 item, 6 gudang, 22 movement; stok negatif 0, yatim 0, duplikat 0, over-reserve 0)
- `php artisan accounting:audit-integrity` → PASS (521 jurnal seimbang, AR match alokasi+deposit, AP match jurnal, payroll vs status match)
- `php artisan inventory:reconcile-legacy` → 4 kombinasi item+gudang, variance terjelaskan (movement ERP setelah opening import)
- `php artisan seo:audit` → PASS=469 WARNING=31 FAIL=0, broken internal links 0
- `php artisan seo:sitemap` → 1.000 indexable urls di 5 child sitemap
- `php artisan import:legacy --dry-run` → preview tanpa write
- `npm run build` → sukses (Vite)
- `php artisan migrate:status` → tanpa duplikasi, semua Ran


## Sesi hardening ASTRA (2026-09-08)

69 test baru, semua PASS:

- FuelLifecycleTest (6): receipt/journal, negative-tank block, issue LPH+cost, double-post, CRITICAL anomaly + notifikasi, transfer antar tangki
- TireLifecycleTest (7): install/remove/rotate/repair + slot & double-mount guards
- BudgetControlTest (7): block/warn enforce, override permission, commitment, actuals dari jurnal
- HseWorkflowTest (11): lifecycle report->action->verify->close, notifikasi severity, permit expiry
- PaymentAllocationTest (7): FIFO lintas invoice, partial, overpay ditolak, jurnal balanced
- DepositLedgerTest (7): signed walk, adjustment +/-, material-credit terpisah, no-trace on failure
- StockIntegrityTest (9): ticket sekali pakai, reservasi enforced, company-match, dispatch guards
- ProcurementGuardTest (4): cumulative over-bill, pay-only-posted, unique supplier invoice
- ProductionYieldTest (4): yield mustahil ditolak, posting stok+jurnal, double-post
- WeighbridgeGuardTest (5): override POSTED/zero-net ditolak, before-after audit, post net>0
- SparepartReturnReversalTest (2): reversal jurnal + WO cost turun, issue ke WO closed ditolak

