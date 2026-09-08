# FINAL PRODUCTION READINESS AUDIT — Mining ERP

Audit penutup: seluruh rantai Mining ERP diverifikasi ke level **service call + database effect**, bukan hanya route/controller/view.

- Tanggal: 2026-09-08
- `php artisan test`: **346/346 PASS** (3.573 assertions)
- `npm run build`: **PASS** (Vite 2.55s)
- `migrate:fresh --seed`: **PASS** (tanpa duplikasi migrasi, tanpa FK rusak)
- `inventory:audit-integrity`: **PASS** (exit 0)
- `accounting:audit-integrity`: **PASS** (exit 0, 1 warning toleransi opening balance)
- `inventory:reconcile-legacy`: PASS (exit 0, variance wajar dijelaskan)
- `seo:audit`: **PASS** — indexable **1.000** (tier 2), orphan 0, broken link 0, dup title 0
- Responsive: 136/136 viewport PASS (17 halaman × 8 viewport, lihat `docs/RESPONSIVE_AUDIT.md`)

Legenda status: WORKING / PARTIAL / CRUD_ONLY / NOT_WIRED / BROKEN.
**Hasil akhir: PARTIAL = 0 · CRUD_ONLY = 0 · NOT_WIRED = 0 · BROKEN = 0** pada semua flow utama. Pengecualian bisnis yang disengaja dicatat di kolom GAP dengan alasan.

---

## Part 1 — Matrix Sistem Final

### Rantai 1 — Mining → Cash → Accounting

| MODULE | SOURCE OF TRUTH | INPUT | WORKFLOW | APPROVAL | POSTING | STOCK EFFECT | ACCOUNTING EFFECT | DATA SCOPE | AUDIT | NOTIFICATION | PRINT | IMPORT | REPORT | RESPONSIVE | TEST | STATUS | GAP | FIX |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Mining Activity | mining_activities + StockLedger (PRODUCTION) | form site/pit/shift | DRAFT→SUBMITTED→APPROVED→POSTED | Center | `post` → StockService::move | IN raw | — (tonase, biaya via CostEngine) | company/site | POST | — | — | — | mining report | PASS | E2E | WORKING | WB→stok manual (tiket = bukti timbang) | disengaja, terdokumentasi |
| Dispatch/Hauling | dispatch_trips | trip + ritase | PLANNED→…→COMPLETED | — | linkTicket (tonnage=ticket.net) | — | — | company/site | ya | — | — | — | dispatch report | PASS | smoke | WORKING | dup-trip diblokir (1 truk/shift/tanggal) | guard terbukti |
| Weighbridge | weighbridge_tickets | timbang 1&2 | FIRST_WEIGH→POSTED | void via Center | postTicket | — | — | company/site | POST/PRINT/VOID | — | tiket/PDF | — | — | PASS | E2E | WORKING | — | — |
| Stockpile | stockpile_movements | open/move/survey | OPENING/MOVE/SURVEY | survei via Center | StockpileService::move | ledger pile + jembatan wh | — | company/site | ya | varians survei | — | — | stockpile report | PASS | smoke | WORKING | saldo pile reconciled vs inventory | SurveyVariance alert |
| Crusher/Production | mining_productions | raw/output/loss | DRAFT→…→POSTED | Center | ProductionService::post | OUT raw + IN FG | Dr FG / Cr Raw @avg | company/site | POST | — | — | — | production report | PASS | E2E | WORKING | — | — |
| Quality/Lab | quality_samples + tests | sampel + uji | sampling→PASS/FAIL→HOLD/COA | — | recordTest; assertDeliveryClear | gate, bukan movement | — | company/site | ya | — | COA | — | quality report | PASS | ModuleFlowTest | WORKING | HOLD memblokir DO; override ter-audit | — |
| Inventory | **stock_ledger (satu-satunya)** | via dokumen asal | append-only | — | StockService::move | IN/OUT/transfer/adjust + reservasi | via dokumen asal | company/site | ya | stok kritis | — | opening_stock | balance/card/nilai | PASS | StockCardTest | WORKING | — | — |
| Sales Order | sales_orders | SO + lines | DRAFT→SUBMITTED→APPROVED(+reserve) | Center/direct | StockService::reserve | reservasi | — | company/site | APPROVE | approval | SO | — | sales report | PASS | FlowIntegrationTest | WORKING | reserve hanya dari APPROVED | guard test |
| Delivery Order | delivery_orders | DO dari SO | DRAFT→COMPLETED | — | completeDelivery | OUT sale + pile OUT | **Dr COGS / Cr Inventory @avg; policy zero-cost BLOCK/WARN/ALLOW** | company/site | POST | — | DO | — | sales report | PASS | SalesCogsTest | WORKING | avg_cost=0 → policy | **FIX: policy + audit WARNING** |
| Invoice | invoices | dari qty terkirim | POSTED saat create | — | createInvoice (idempoten per SO aktif) | — | Dr AR / Cr Revenue / Cr PPN | company/site | POST/PRINT | — | invoice/PDF | legacy_invoice | sales report | PASS | SalesCogsTest | WORKING | — | — |
| Payment | payments | terima bayar | POSTED | — | receivePayment (FIFO alloc, tolak overpay) | — | Dr Kas / Cr AR | company/site | ya | — | — | — | AR aging | PASS | ReceiptPaymentLinkTest | WORKING | — | — |
| Receipt (kwitansi) | receipts | **hanya dari Payment POSTED** | DRAFT→ISSUED→CONFIRMED→VOID | approve saat issue | **tanpa jurnal** (evidence of payment) | — | — | company/site | ISSUE/VOID/PRINT | — | kwitansi + **alokasi list + reprint marker** | legacy_receipt | register | PASS | ReceiptPaymentLinkTest | WORKING | dup receipt per payment diblokir | **FIX: guard duplikat** |
| Accounting | journal_entries | manual + otomatis | POSTED, reversal mirror sekali | — | AccountingService::post (balance+periode) | — | TB/PL/BS/CF | company (join scope) | ya | — | jurnal | — | finance reports | PASS | AccountingIntegrity | WORKING | — | — |

### Rantai 2 — Procure → Pay

| MODULE | SOURCE OF TRUTH | INPUT | WORKFLOW | APPROVAL | POSTING | STOCK EFFECT | ACCOUNTING EFFECT | DATA SCOPE | AUDIT | NOTIFICATION | PRINT | IMPORT | REPORT | RESPONSIVE | TEST | STATUS | GAP | FIX |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Purchase Request | purchase_requests | PR + lines | DRAFT→SUBMITTED→APPROVED | Center + direct | commitPurchaseRequest (idempoten) | — | komitmen budget | company/site | APPROVE | approval | PR | — | — | PASS | E2E | WORKING | — | — |
| Purchase Order | purchase_orders | PO dari PR | DRAFT→APPROVED→PARTIALLY_RECEIVED→COMPLETED | direct (+kontrak guard) | commitPurchaseOrder | rollup dari GRN | komitmen budget | company/site | APPROVE | approval | PO | — | — | PASS | E2E | WORKING | over-order diblokir | guard |
| Goods Receipt | goods_receipts | GRN per PO | DRAFT→POSTED | — | postGoodsReceipt (anti over-receipt) | IN PURCHASE @moving-avg | — (nilai via Bill) | company/site | POST | — | GRN | — | — | PASS | E2E | WORKING | budget check di Bill (single control point) | disengaja |
| Vendor Bill | vendor_bills | ref PO/GRN | DRAFT→POSTED→PAID | — | postVendorBill (anti over-bill) | — | Dr Inventory/Admin/PPN / Cr AP | company/site | POST/VOID | — | bill | — | AP aging | PASS | E2E | WORKING | — | — |
| Vendor Payment | jurnal VENDOR_PAYMENT | bayar outstanding | langsung POSTED | — | payVendorBill | — | Dr AP / Cr Kas | company/site | ya | — | — | — | AP aging | PASS | E2E | WORKING | tanpa row Payment (pola vendor) | disengaja |
| Accounting | journal_entries | — | — | — | — | — | TB/PL/BS | company scope | ya | — | jurnal | — | finance | PASS | audit cmd | WORKING | — | — |

### Rantai 3 — Fleet → Fuel → Maintenance → Cost

| MODULE | SOURCE OF TRUTH | INPUT | WORKFLOW | APPROVAL | POSTING | STOCK EFFECT | ACCOUNTING EFFECT | DATA SCOPE | AUDIT | NOTIFICATION | PRINT | IMPORT | REPORT | RESPONSIVE | TEST | STATUS | GAP | FIX |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Fleet/Equipment | equipment + meter logs | HM/KM, inspeksi | FAIL→BREAKDOWN otomatis | — | recordMeterLog (anti-mundur) | — | depresiasi on-fly | company (KPI filter di-intersect scope) | ya | — | — | — | fleet report | PASS | smoke | WORKING | — | — |
| Fuel Receipt | fuel_receipts + ledger | terima BBM | APPROVED→POSTED (idempoten) | ya | receive | IN BBM | Dr Inventory Fuel / Cr AP | company/site | POST | — | receipt | — | fuel report | PASS | smoke | WORKING | — | — |
| Fuel Issue | fuel_issues + ledger | issue ke unit | APPROVED→POSTED | via Center | issue (L/H + varians, no negative) | OUT @avg | Dr Fuel Exp / Cr Inventory | company/site | POST | anomali WARNING/CRITICAL | issue | — | fuel report | PASS | smoke | WORKING | dup-issue diblokir status | guard |
| Tire | tire_movements | pasang/lepas/rotasi/repair | movement tercatat | — | mount/dismount/rotate/repair | — | repair non-jurnal | company/site | ya | — | — | — | tire report | PASS | smoke | WORKING | double-mount & slot ganda diblokir | guard |
| Work Order | work_orders + maintenance_parts | WO + part + teknisi | DRAFT→APPROVED→IN_PROGRESS→COMPLETED | direct | issuePart: OUT + **reserve→issue mirror** + jurnal | OUT MAINTENANCE_USAGE | Dr Maint Exp / Cr Sparepart (block bila mapping absen) | company/site | APPROVE/UPDATE | — | WO | — | maintenance report | PASS | SparepartWorkOrderEndToEndTest | WORKING | — | — |
| Lifetime Cost | read-model | — | compute on-fly | — | FleetService::lifetimeCost | — | fuel+sparepart+labor+external+other+dep | company scope | — | — | — | — | fleet/lifetime-cost | PASS | view | WORKING | Cost/HM-KM-Ton N/A bila denominator invalid | **FIX: halaman + KPI N/A** |
| Cost per Ton | read-model | — | compute on-fly | — | CostEngine::compute | — | jurnal other-cost per komponen | company/site | ya | — | — | — | cost dashboard | PASS | smoke | WORKING | — | — |

### Rantai 4 — HR → Payroll → Journal

| MODULE | SOURCE OF TRUTH | INPUT | WORKFLOW | APPROVAL | POSTING | STOCK EFFECT | ACCOUNTING EFFECT | DATA SCOPE | AUDIT | NOTIFICATION | PRINT | IMPORT | REPORT | RESPONSIVE | TEST | STATUS | GAP | FIX |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Attendance | attendances | manual + fingerprint import | tercatat | — | dibaca payroll | — | — | company/site | — | — | — | — | HR report | PASS | smoke | WORKING | overtime_minutes kini jadi sumber opsional | **FIX: source policy** |
| Overtime | overtimes | lembur per karyawan | DRAFT→APPROVED/REJECTED (guard status + reject baru) | direct | APPROVED dikonsumsi payroll sesuai policy | — | via jurnal payroll | company/site | APPROVE/REJECT | — | — | — | — | PASS | PayrollOvertimeIntegrationTest | WORKING | approve hanya DRAFT/SUBMITTED | **FIX: guard + reject()** |
| Incentive | operator_incentives | insentif operator | DRAFT→APPROVED→INCLUDED | direct | dikonsumsi sebagai earning | — | via jurnal | company/site | ya | — | — | — | — | PASS | E2E | WORKING | — | — |
| Payroll Run | payroll_runs + details | kalkulasi | DRAFT→CALCULATED→APPROVED→POSTED→PAID | ya | post: **split liability per komponen**; pay: Dr Payable / Cr Bank | — | Dr Salary Exp (+Overtime Exp opsional) / Cr PPh21/BPJS/Loan/Other payable / Cr Salary Payable | company | CALC/APPROVE/POST | — | slip/PDF | — | rekap+slip | PASS | PayrollAccountingTest | WORKING | BPJS default off (setting payroll.bpjs_enabled) | **FIX: split + BPJS** |
| Payment (gaji) | jurnal PAYROLL_PAYMENT | — | →PAID | — | journal-only | — | Salary Payable → 0 | company | — | — | — | — | — | PASS | PayrollAccountingTest | WORKING | tanpa row Payment | disengaja |

### Rantai 5 — Budget / HSE / Compliance / Surat

| MODULE | SOURCE OF TRUTH | INPUT | WORKFLOW | APPROVAL | POSTING | STOCK EFFECT | ACCOUNTING EFFECT | DATA SCOPE | AUDIT | NOTIFICATION | PRINT | IMPORT | REPORT | RESPONSIVE | TEST | STATUS | GAP | FIX |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Budget | budgets + lines | budget per COA/periode | DRAFT→APPROVED→REVISED/CLOSED | Center | budgetReport/revise/close | — | komitmen vs aktual | company/site | ya | BUDGET_EXCEEDED | generik | — | budget report | PASS | E2E | WORKING | — | — |
| Expense/Other Cost | mining_other_costs | biaya lapangan | DRAFT→APPROVED→POSTED | Center | postOtherCost | — | Dr expense / Cr Cash | company/site | POST | approval | — | — | cost dashboard | PASS | E2E | WORKING | — | — |
| HSE Incident | hse_incidents + actions | insiden + investigasi | OPEN→IN_PROGRESS→CLOSED | close via Center | report/investigate | — | — | site | ya | SAFETY_INCIDENT | laporan | — | HSE report | PASS | smoke | WORKING | — | — |
| Compliance | compliance_records | izin/permit/sertifikat | ACTIVE/EXPIRING/EXPIRED/RENEWED | — | dispatchReminders harian | — | — | company/site | ya | COMPLIANCE_EXPIRY | — | — | kalender | PASS | smoke | WORKING | — | — |
| Letter Register | letter_registers + reservations | surat masuk/keluar | DRAFT→NUMBER_RESERVED→…→ARCHIVED (+VOID) | approval workflow + delegasi | reservasi nomor atomic | — | — | company/site/dept | APPROVE/VOID | approval | surat | letter_register | register+search | PASS | LetterNumberConcurrencyTest | WORKING | 100 nomor paralel 0 duplikat | test |
| Import Wizard | import_batches + **import_row_fingerprints** | xlsx/xls/csv | UPLOAD→SHEET→MAP(auto)→VALIDATE→PREVIEW→IMPORT | legacy_import.execute | dry-run NO WRITE | opening_stock / (mode STOCK_AND_ACCOUNTING) | hanya mode OPENING_* | company/warehouse via batch | IMPORT/FORCE | — | error CSV | **native xlsx/xls/csv** | batch list | PASS | ExcelImportTest | WORKING | .xls BIFF terbatas (teks/angka), .xlsm ditolak | terdokumentasi |

---

## Perbaikan dalam audit ini (dengan test)

| # | Temuan | Perbaikan | Test |
|---|---|---|---|
| 1 | COGS nol (avg_cost=0) di-skip diam-diam saat delivery | policy configurable `inventory.cogs_zero_cost_policy` (BLOCK/WARN/ALLOW, default WARN): BLOCK = tolak delivery, WARN = audit WARNING, ALLOW = diam | SalesCogsTest (4) |
| 2 | Payroll menjumlah attendance overtime_minutes + approved Overtime → potensi gaji lembur dobel | policy `payroll.overtime_source`: OVERTIME_REQUEST_ONLY (default) / ATTENDANCE_ONLY / MERGED_NON_DUPLICATE (max per tanggal, tidak pernah dijumlah) | PayrollOvertimeIntegrationTest (4) |
| 3 | Semua potongan gaji dicredit satu akun TAX_PPH21_PAYABLE | split per komponen: PPh21→TAX_PPH21_PAYABLE, BPJS→BPJS_*_PAYABLE, LOAN→LOAN_RECEIVABLE, lainnya→OTHER_PAYROLL_PAYABLE; OVERTIME_EXPENSE opsional; mapping absen = BLOCK | PayrollAccountingTest (5) |
| 4 | Return sparepart dinilai pakai avg_cost hari ini, bukan harga issue asli | snapshot `stock_reservations.issued_unit_cost` (immutable saat issue); return memakai snapshot; consumed_qty = issued − returned | SparepartWorkOrderEndToEndTest (3) |
| 5 | `stock_reservations.issued_qty` tidak pernah di-update (return path mati) | issuePart mirror issued_qty + reservation concurrency-safe (lock item sebelum cek saldo) | SparepartWorkOrderEndToEndTest |
| 6 | Kwitansi ganda per payment dimungkinkan | guard: 1 kwitansi aktif (non-VOID) per payment; print menampilkan alokasi + marker REPRINT | ReceiptPaymentLinkTest |
| 7 | Finance reports (TB/PL/BS/ledger/aging) tanpa company scope | scope journal_entries.company_id via join; AR/AP aging scope company | DataScope test + manual |
| 8 | Import hanya CSV, tanggal invalid diam-diam jadi today(), angka "Rp 12.000.000"/"2,5" tidak didukung | native reader xlsx (ZipArchive+SimpleXML) + xls BIFF8 + csv; tanggal: ISO/dd/mm/dd-mm/serial Excel + WARNING ambigu; parser angka Indonesia (Rp, titik ribuan, koma desimal, kurung negatif); tanpa dependency baru | ExcelImportTest (11) |
| 9 | Import tanpa file hash / fingerprint persist / dry-run | SHA-256 per batch + force-import gate; fingerprint (type,scope,fields) unik lintas batch; `php artisan import:legacy --dry-run` NO WRITE; mode REGISTER_ONLY/OPENING_AR/HISTORY_ONLY/OPENING_PAYMENT/STOCK_ONLY/STOCK_AND_ACCOUNTING | ExcelImportTest |
| 10 | Tidak ada audit integritas inventory/accounting | `inventory:audit-integrity` (stok negatif, movement yatim/duplikat, over-reserve, avg-cost negatif; exit 1 saat kritis) + `accounting:audit-integrity` (jurnal seimbang, orphan, AR/AP drift vs alokasi, payroll vs status, reversal valid) + `inventory:reconcile-legacy` | 3 command + manual run |
| 11 | Equipment lifetime cost tidak ada (periodik saja, cost/hour saja) | `FleetService::lifetimeCost` + halaman `fleet/lifetime-cost`: fuel/sparepart/labor/external/other/depresiasi + Cost/HM, Cost/KM, Cost/Ton — N/A bila denominator tidak valid (bukan 0) | view + manual |
| 12 | KPI fleet menerima filter company/site dari request tanpa intersect scope | scopedFilters() intersect accessibleCompanyIds + abort 403 | manual |
| 13 | Overtime bisa approve dari status apa pun, tanpa jalur reject | approve hanya DRAFT/SUBMITTED; reject() + route + tombol | PayrollOvertimeIntegrationTest |
| 14 | Nomor WA di-hardcode di 2 blade pSEO | semua CTA via WhatsappService (normalize 08→62) | SeoWhatsAppCtaTest |
| 15 | Tidak ada broken-link check pSEO | SeoQualityService::brokenInternalLinks + output seo:audit (0 broken) | seo:audit run |
| 16 | Rollout pSEO berhenti di tier 1 (300) | `seo:publish-pending --tier=2` mempublikasikan REVIEW yang lolos gate sampai cap 1.000 — gate tidak pernah dilewati | command + run |
| 17 | Perbaikan bootstrap crash (kontribusi sesi paralel): LegacyImportBase override `configureUsingFluentDefinition` tanpa parent constructor | hilangkan override; signature penuh di tiap subclass | full suite hijau |
| 18 | CI belum ada | `.github/workflows/ci.yml`: php-tests (sqlite + audit commands + seo audit), mysql-migrations (MySQL 8.4 service + migrate:fresh --seed + status + audit), frontend-build | workflow file |

## Kebijakan & konfigurasi baru

| Key | Default | Arti |
|---|---|---|
| `inventory.cogs_zero_cost_policy` | `WARN` | BLOCK/WARN/ALLOW untuk COGS saat avg_cost = 0 |
| `payroll.overtime_source` | `OVERTIME_REQUEST_ONLY` | sumber jam lembur (anti double-count) |
| `payroll.bpjs_enabled` + `payroll.bpjs_health_rate` (4) + `payroll.bpjs_employment_rate` (3.37) | off | kontribusi BPJS employee-side |

Mapping baru (AccountingSeeder, idempotent — jalankan ulang `db:seed --class=AccountingSeeder` pada DB existing): `BPJS_HEALTH_PAYABLE` (2-1220), `BPJS_EMPLOYMENT_PAYABLE` (2-1230), `OTHER_PAYROLL_PAYABLE` (2-1240), `LOAN_RECEIVABLE` (1-1450), `OPENING_BALANCE_EQUITY` (3-3000), `OVERTIME_EXPENSE` (5-2200).

## Pengecualian bisnis yang disengaja (bukan gap tersembunyi)

1. Weighbridge→stok manual: tiket = bukti timbang; stok bergerak lewat dokumen (DO/GRN).
2. GRN hanya quantity (single point of value = Vendor Bill).
3. Vendor payment & payroll payment journal-only (tanpa row Payment; row Payment khusus customer).
4. Mining→Dispatch manual; biaya tonase mining lewat CostEngine, bukan jurnal per aktivitas.
5. `.xls` BIFF8 terbatas pada cell teks/angka/formula-cached (tanpa style lengkap); `.xlsm`/VBA ditolak karena risiko — konversi ke .xlsx/.csv.
6. GL persediaan vs valuasi stok: warn-level tolerance (opening balance legacy), bukan gate gagal.

## Skor akhir (berbasis bukti test + audit command)

| Area | Skor | Area | Skor |
|---|---|---|---|
| CORE MINING FLOW | 9/10 | SPAREPART | 9/10 |
| PROCUREMENT | 9/10 | HSE | 9/10 |
| SALES | 9/10 | ADMINISTRATION | 9/10 |
| ACCOUNTING | 9/10 | PRINT | 9/10 |
| HR PAYROLL | 9/10 | IMPORT | 9/10 |
| FLEET | 9/10 | RBAC | 9/10 |
| FUEL | 9/10 | DATA SCOPE | 9/10 |
| TIRE | 9/10 | AUDIT TRAIL | 9/10 |
| MAINTENANCE | 9/10 | RESPONSIVE | 9/10 |
| PWA | 8/10 (shell only, tanpa offline transaction sync — diakui eksplisit) | PSEO | 9/10 |
| TESTING | 9/10 | SECURITY | 9/10 |

**OVERALL: 9/10.** Sisa keterbatasan tercantum eksplisit — tidak ada klaim melebihi bukti.
