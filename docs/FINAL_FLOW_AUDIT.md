# Final Flow Integration Audit — Mining ERP

Audit seluruh repo terhadap blueprint: route + controller + view TIDAK dianggap
selesai sebelum efek downstream terbukti di kode. Metode: baca controller,
service, model, route per rantai + bukti automated test.

- Tanggal: 2026-09-07 · `php artisan test`: **150/150 PASS** (2516+ assertions)
- Responsive: 136/136 viewport PASS (`docs/RESPONSIVE_AUDIT.md`)
- Status: WORKING / PARTIAL / CRUD ONLY / NOT WIRED / BROKEN
- Hasil: **CRUD ONLY = 0 · NOT WIRED = 0 · BROKEN = 0**

Kolom MOBILE = hasil responsive audit + bukti screenshot (semua PASS).
Keterbatasan API mobile dicatat di baris modul bila relevan.

## Rantai 1 — Mining → Cash → Accounting

| Module | Input | Workflow | Approval | Posting | Stock Effect | Accounting Effect | Data Scope | Audit Trail | Notification | Report | Print | Mobile | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Mining Activity | form harian site/pit/shift/alat/operator | DRAFT→SUBMITTED→APPROVED→POSTED | Center | `MiningActivityController@post` → `StockService::move(PRODUCTION)` | IN raw ke pile/gudang | — (tonase saja, tanpa jurnal) | company/site | POST | — | mining report + CSV | — | PASS | PARTIAL |
| Dispatch/Hauling | trip + ritase | PLANNED→LOADING→HAULING→DUMPED→COMPLETED | — | `linkTicket` tarik `tonnage=ticket.net` | — | — | company/site | ya | — | dispatch report | — | PASS | PARTIAL |
| Weighbridge | timbang 1 & 2 | FIRST_WEIGH→COMPLETE→POSTED | void via Center | `postTicket` status saja | — (net tidak auto-masuk stok) | — | company/site | POST/PRINT/VOID | — | — | tiket/reprint/PDF | PASS | PARTIAL |
| Stockpile | saldo awal, movement, survei | OPENING/MOVE/SURVEY_ADJUSTMENT | survei via Center | `StockpileService::move` ledger + cek saldo | ledger pile + jembatan warehouse | — | company/site | ya | varians survei | stockpile report | — | PASS | PARTIAL |
| Production Batch | input raw/output/loss | DRAFT→SUBMITTED→APPROVED→POSTED | Center | `ProductionService::post`: OUT raw + IN FG + pile IN | OUT raw, IN FG (moving avg) | Dr FG / Cr Raw | company/site | POST | — | production report | — | PASS | WORKING |
| Quality | sampel + uji | sampling→PASS/FAIL→HOLD/COA | — | `recordTest` auto PASS/FAIL vs spec; `assertDeliveryClear` blokir DO | — (gate, bukan movement) | — | company/site | ya | — | quality report | COA | PASS | WORKING |
| Inventory | — (sink) | ledger append-only | transfer/adjust via Center | `StockService::move` satu-satunya penulis `stock_ledger` | IN/OUT/transfer/adjust + reservasi | via dokumen asal | company/site | ya | stok kritis | balance/card/nilai | — | PASS | WORKING |
| Sales Order | SO + lines (kontrak + price list guard) | DRAFT→SUBMITTED→APPROVED (+reserve) | Center/direct | `reserve` → `StockService::reserve` | reservasi DO | — | company/site | APPROVE | approval | sales report | SO | PASS | WORKING |
| Delivery Order | DO dari SO APPROVED (anti over-delivery) | DRAFT→COMPLETED | — | `completeDelivery`: gate QC + OUT sale + pile OUT + tiket POSTED | OUT sale + pile SALES_OUT | — (tanpa HPP otomatis) | company/site | ya | — | sales report | DO | PASS | WORKING |
| Invoice | generate dari qty terkirim (satu SO → satu faktur aktif) | dibuat langsung POSTED | — | `createInvoice` + idempotency guard | — | Dr AR / Cr Revenue / Cr PPN + TaxTransaction + deposit opsional | company/site | POST/PRINT | — | sales report | invoice/PDF | PASS | WORKING |
| Payment (customer) | terima bayar + alokasi FIFO | POSTED→PAID/PARTIALLY | — | `receivePayment`, tolak overpay | — | Dr Kas / Cr AR | company/site | ya | — | AR aging | — | PASS | WORKING |
| Accounting | jurnal manual + otomatis | POSTED, reversal mirror sekali | — | `AccountingService::post` (balance + periode) | — | TB/PL/BS/CF/ledger | — | ya | — | finance reports | jurnal | PASS | WORKING |

Gap rantai 1 yang disengaja (terdokumentasi, bukan rusak): Mining→Dispatch
manual; WB→stok manual (tiket hanya bukti timbang); tanpa jurnal HPP otomatis
saat DO/Invoice; tonase mining tanpa jurnal biaya.

## Rantai 2 — Procure → Pay

| Module | Input | Workflow | Approval | Posting | Stock Effect | Accounting Effect | Data Scope | Audit Trail | Notification | Report | Print | Mobile | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Purchase Request | PR + lines | DRAFT→SUBMITTED→APPROVED | Center + direct (komitmen identik) | `commitPurchaseRequest` (idempoten) | — | komitmen budget (non-jurnal) | company/site | APPROVE | approval | — | PR | PASS | WORKING |
| Purchase Order | PO dari PR APPROVED (opsional) | DRAFT→APPROVED→PARTIALLY_RECEIVED→COMPLETED | direct (+kontrak guard) | `commitPurchaseOrder` + release PR | rollup status dari GRN POSTED | komitmen budget | company/site | APPROVE | approval | — | PO | PASS | WORKING |
| GRN | terima per PO (anti over-receipt) | DRAFT→POSTED | — | `postGoodsReceipt`: IN + moving avg | IN PURCHASE | — (nilai via Bill) | company/site | POST | — | — | GRN | PASS | PARTIAL |
| Vendor Bill | tagihan ref PO/GRN (anti over-billing) | DRAFT→POSTED→PAID, VOID reverse | — | `postVendorBill` | — | Dr Inventory/Admin/PPN / Cr AP + consume budget | company/site | POST/VOID | — | AP aging | — | PASS | WORKING |
| Vendor Payment | bayar outstanding | langsung POSTED | — | `payVendorBill` Dr AP / Cr Kas | — | AP→PAID/PARTIALLY | company/site | ya | — | AP aging | — | PASS | PARTIAL |
| Accounting | — | — | — | — | — | jurnal VENDOR_BILL/PAYMENT tampil di TB/PL/BS | — | ya | — | finance | jurnal | PASS | WORKING |

Gap rantai 2 yang disengaja: GRN hanya qty (single point of value = Bill);
bayar vendor tanpa row `Payment`/alokasi (pola customer-only); link PR→PO dan
GRN→Bill longgar-nullable (validasi di guard, bukan FK keras).

## Rantai 3 — Fleet → Fuel → Maintenance → Cost

| Module | Input | Workflow | Approval | Posting | Stock Effect | Accounting Effect | Data Scope | Audit Trail | Notification | Report | Print | Mobile | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Fleet/Equipment | meter log, inspeksi | FAIL→BREAKDOWN otomatis | — | `recordMeterLog` (anti-mundur), KPI on-fly | — | depresiasi on-fly (non-jurnal) | company/site | ya | — | fleet report | — | PASS | PARTIAL |
| Fuel Receipt | terima BBM | APPROVED→POSTED (idempoten) | ya | `receive` ledger IN | IN BBM | Dr Inventory Fuel / Cr AP | company/site | POST | — | fuel report | receipt | PASS | WORKING |
| Fuel Issue | issue ke unit | APPROVED→POSTED | via Center | `issue`: OUT + L/H + varians vs standar | OUT (cek saldo, avg-cost) | Dr Fuel Exp / Cr Inventory | company/site | POST | anomali WARNING/CRITICAL | fuel report | issue | PASS | WORKING |
| Fuel Transfer | antar tangki satu company | DRAFT→POSTED langsung | — | `transfer` OUT+IN @avg-cost | pindah tangki | — (internal, benar) | company/site | POST | — | — | — | PASS | PARTIAL |
| Tank/Dip | onset + sistem | ukur→PENDING→APPROVED | ya | `dip` varians; approve tanpa bukukan susut | — (selisih tak dibukukan) | — | company/site | ya | threshold | — | — | PASS | PARTIAL |
| Tire | pasang/lepas/rotasi/repair | movement tercatat | — | `tire_movements` + guard slot | — | repair via note (non-jurnal) | company/site | ya | — | tire report | — | PASS | PARTIAL |
| Work Order | WO + task/part/teknisi | DRAFT→APPROVED→IN_PROGRESS→COMPLETED | direct (guard status di complete) | `issuePart`: OUT + cost + jurnal; `complete` terkunci APPROVED/IN_PROGRESS | OUT MAINTENANCE_USAGE | Dr Maint Exp / Cr Sparepart (part); LABOR non-jurnal | company/site | APPROVE/UPDATE | — | maintenance report | WO | PASS | PARTIAL |
| Schedule | PREVENTIVE/CORRECTIVE + interval | next_due dihitung → generate WO | — | `generateDueWorkOrders`: WO DRAFT per jadwal jatuh tempo (anti-duplikat) | — | — | via aset/alat | CREATE | due via alert:scan | — | — | PASS | PARTIAL |
| Cost per Ton | — (read-model) | compute on-fly | approve/post other-cost | `CostEngine::compute`: fuel + maint + tire + depresiasi + payroll ÷ batch POSTED | — | other-cost per komponen dijurnal | company/site | ya | — | cost dashboard + CSV | — | PASS | WORKING |
| Accounting | — | — | — | — | — | receipt/issue/part/other-cost terjurnal | — | ya | — | finance | jurnal | PASS | WORKING |

## Rantai 4 — HR → Payroll → Journal

| Module | Input | Workflow | Approval | Posting | Stock Effect | Accounting Effect | Data Scope | Audit Trail | Notification | Report | Print | Mobile | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Attendance | manual + import fingerprint | tercatat (late otomatis) | — | dibaca payroll (`overtime_minutes` belum diisi) | — | — | company/site | — | — | HR report | — | PASS | PARTIAL |
| Overtime | lembur per karyawan | DRAFT→APPROVED (guard ganda + audit) | direct | APPROVED tercatat + relasi approver | — | — (belum dikonsumsi payroll — keputusan desain, lihat bawah) | company/site | APPROVE | — | — | — | PASS | PARTIAL |
| Incentive | insentif operator | DRAFT→APPROVED→INCLUDED_IN_PAYROLL | direct | dikonsumsi payroll sebagai earning | — | via jurnal payroll | company/site | create/approve | — | — | — | PASS | WORKING |
| Payroll Run | kalkulasi per periode | DRAFT→CALCULATED→APPROVED→POSTED→PAID (dienforce) | ya | `post`: jurnal gaji; `pay`: Dr Payable / Cr Bank | — | Dr Salary Exp / Cr PPh21 Payable / Cr Salary Payable | company | calculate/approve/post | — | rekap + slip | slip/PDF | PASS | WORKING |
| Payment (gaji) | bayar run POSTED | →PAID | — | journal-only (tanpa row Payment) | — | Salary Payable → 0 | company | — | — | — | — | PASS | PARTIAL |

Keputusan desain (bukan defect): agregasi `Overtime APPROVED → payroll`
belum diimplementasikan — payroll memakai `attendances.overtime_minutes`
(rumus basic/173×1.5). Mengubahnya mengubah nominal gaji sehingga diputus
eksplisit bersama owner sebelum di-wire. PPh21 memakai tarif settings;
pengali lembur masih konstanta (tercatat di kode).

## Rantai 5 — Budget → Actual → Variance & HSE → Compliance

| Module | Input | Workflow | Approval | Posting | Stock Effect | Accounting Effect | Data Scope | Audit Trail | Notification | Report | Print | Mobile | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Budget | budget + lines per COA/periode | DRAFT→APPROVED→REVISED/CLOSED | Center | `budgetReport`, revise, close | — | komitmen vs aktual | company/site | ya | approval | budget report + CSV | generik | PASS | WORKING |
| Expense/Other Cost | biaya lapangan | DRAFT→APPROVED→POSTED | Center | `postOtherCost` per komponen | — | Dr expense / Cr Cash | company/site | POST | approval | cost dashboard | — | PASS | WORKING |
| Actual/Variance | otomatis dari jurnal POSTED | — | — | `actualForLine` + `lineReport` (used%) | — | — | company/site | — | BUDGET_EXCEEDED via scan | variance report | — | PASS | WORKING |
| Approval Center | semua tipe transaksi | PENDING→APPROVED/REJECTED/RETURNED | workflow + delegasi + auto-approve | `ApprovalResolver` side-effect per tipe | — | via handler (PR commit, survei, HSE) | company/site | APPROVE/REJECT | ApprovalPending | — | — | PASS | WORKING |
| HSE Incident | laporan + investigasi | OPEN→IN_PROGRESS→CLOSED | close via Center | `report/investigate`, auto-notify HIGH/CRITICAL | — | — | site | ya | SAFETY_INCIDENT | HSE report | laporan | PASS | WORKING |
| Corrective Action | aksi + PIC + due + evidence | OPEN→DONE→VERIFIED | verifikasi wajib | blokir close bila belum verified | — | — | site | ya | — | — | — | PASS | WORKING |
| Permit (PTW) | izin kerja + masa berlaku | DRAFT→SUBMITTED→APPROVED | via Center | expiry scan H-14 | — | — | company/site | ya | expiry | dashboard | — | PASS | PARTIAL |
| Compliance | registrasi + renew + kalender | manual (reminder harian 07:00) | — | `dispatchReminders` | — | — | company/site | ya | COMPLIANCE_EXPIRY | kalender | — | PASS | PARTIAL |

Gap rantai 5 yang disengaja: permit APPROVED tanpa transisi ACTIVE
(konsumen menerima APPROVED/ACTIVE); HSE→compliance tanpa auto-register;
GRN tanpa cek budget (single control point = Bill + jurnal manual).

## Perbaikan dalam audit ini (dengan test)

| # | Temuan | Perbaikan | Test |
|---|---|---|---|
| 1 | `Setting::get` 500 saat tabel `settings` belum ada (installer, error pages, seluruh suite merah di path error) | guard `Schema::hasTable` → default | `FlowIntegrationTest::test_setting_get_returns_default_when_table_missing` |
| 2 | Route `POST invoices/{invoice}/post` → method tidak ada (BROKEN) | route mati dihapus (posting terjadi saat create) | suite tetap hijau + `route:list` bersih |
| 3 | `Overtime::approvedBy` hilang → index 500 (BROKEN) | relasi + guard double-approve + audit | overtime test (HTTP index + approve) |
| 4 | PO tak pernah PARTIALLY_RECEIVED/COMPLETED (dead status) | rollup dari GRN POSTED di `postGoodsReceipt` | partial → complete test |
| 5 | PR via Center tanpa komitmen budget (dual-path) | handler `approvePurchaseRequest` di resolver (idempoten) | center-approve → commitment row |
| 6 | SO tak bisa APPROVED (tanpa `submit`, approve wajib SUBMITTED) | `submit` + route + tombol Ajukan/Reservasi + guard | submit → approve → reserve test |
| 7 | WO `complete()` dari status apa pun | guard APPROVED/IN_PROGRESS | — (guard, tercakup smoke) |
| 8 | `commitPurchaseRequest/Order` throw bila mapping belum ada (fresh install) | return null (no-control) | ApprovalEngineTest hijau kembali |
| 9 | Schedule CRUD ONLY (next_due tak pernah diisi, tanpa generate) | hitung next_due + `generateDueWorkOrders` + tombol + `maintenance:generate-wo` | generate-sekali test |

## Definisi selesai rantai (DoD per rantai)

Rantai dianggap terhubung bila dokumen awal dapat berjalan hingga jurnal/stok
akhir melalui UI/API tanpa langkah manual di database — dibuktikan test
E2E (`EndToEndTest` TEST A–E) + `FlowIntegrationTest` + smoke HTTP.
Pengecualian yang disengaja dicatat di tabel sebagai PARTIAL beserta alasan
bisnisnya; tidak ada yang disembunyikan sebagai WORKING.
