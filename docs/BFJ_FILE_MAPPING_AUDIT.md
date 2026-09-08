# BFJ File Mapping Audit

## A. Finance August 2026 (`Laporan Keuangan Bulan Agustus 2026.xlsx`)
- Meaning: cash-flow + recap sheets. Target: staging `FINANCE` → optional `JournalEntry` via `AccountingService`.
- Mode: RECONCILIATION_ONLY. Mapping: legacy category → `bfj_category_mappings` → COA/cost center/cash account; unmapped queue, no fallback account.
- Validation: OPENING+INFLOW−OUTFLOW=CLOSING per account. Known issue: personal vs company transfers need CLEARING vs TRANSFER_INTERNAL split.

## B. Sales September 2026 (`Penjualan September 2026.xlsx`)
- Meaning: daily sheets (`1 September 2026`…) are transactions; `REKAP BFJ/MAA/RITEL/DEPOSIT` are benchmarks.
- Target: staging `SALES`; optional `DeliveryOrder` link/history. Mode HISTORY_ONLY.
- Mapping: NO DO→legacy_do_number; SOPIR→Employee queue; POLIS→Vehicle (canonical `BG 8506 DS`, original kept); CUSTOMER→alias queue; MATERIAL→Item; RITEL/ALASEN/PERUSAHAAN→CASH/PERSONAL_CLEARING/COMPANY_BANK.
- Validation: gross ≈ volume×price (tolerance 1); formula/rounding variance flagged.

## C. Deposit August 2026 (`DEPOSIT MATERIAL BULAN AGUSTUS 2026.xlsx`)
- Meaning: `SISA DEPOSIT` + customer sheets (TANGGAL/NO DO/SOPIR/POL/MATERIAL/PENJUALAN/DEPOSIT/KET).
- Target: `CustomerDeposit` ledger (DEPOSIT/CONSUMPTION/ADJUSTMENT/REFUND/OPENING_BALANCE, append-only). Mode HISTORY_ONLY.
- Money vs material-credit kept explicit; INV NO 006 stored as `legacy_invoice_reference`, never guessed.

## D. Payroll August 2026 (`PERHITUNGAN GAJI KARYAWAN AGUSTUS 2026.xlsx`)
- Meaning: per-employee day sheets (normal/overtime/standby/BBM/unit/kegiatan); REKAP + SLIP GAJI are benchmarks.
- Target: staging `PAYROLL` → optional legacy payroll run. Mode PAYROLL_RECONCILIATION.
- Overtime types, shift calendars (08–17 / 08–16), break deduction via WorkCalendar; ERP recalculates, compares LEGACY/ERP/VARIANCE.

## E. Correspondence / Invoice / Receipt
- Letter tokens `{SEQ}/{DOC}/{DIV}-{CO}/{ROMAN}/{YEAR}` parsed flexibly (SP/SK/INT/BA/PNG/BAST/PO vs HRD/ADM/FIN/OPS/MKT). Legacy numbers preserved, sequence untouched.
- Invoice register → REGISTER_ONLY `Invoice` DRAFT; status Lunas→PAID, Belum Lunas→OUTSTANDING (source kept).
- Receipt register → HISTORY_ONLY `Receipt`; exact payment linked, never duplicated.

## F. Sparepart warehouse
- Master SPR-xxx → `Item` + flat locations incl. `DINDING`. `STOK LAMA` → OPENING_BALANCE, blank qty → ERROR_MISSING_QTY.
- Issue UNIT/ALAT→Equipment, PEMAKAI→Employee, KEPERLUAN→WO notes; unresolved queued, no fuzzy auto-create.
- Kartu stok / opname / report are reconciliation benchmarks; default no stock effect.
