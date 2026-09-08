# BFJ Real-Data Migration Engine

Profile: `BFJ_LEGACY_2026` (`bfj_import_profiles`). Configurable, migration-only — the app is never hardcoded to BFJ.

## Pipeline

`UPLOAD → DETECT → CLASSIFY → MAP → NORMALIZE → VALIDATE → DUPLICATE CHECK → PREVIEW → DRY RUN → IMPORT → RECONCILE → REPORT`

No ERP writes before explicit IMPORT with impact confirmation. Defaults are history/reconciliation-only:

| Domain | Default mode | Posting effect |
|---|---|---|
| Sales | HISTORY_ONLY | NONE (links DO on exact match, keeps `legacy_do_number`) |
| Deposit | HISTORY_ONLY | NONE (ledger stays source of truth via `DepositService`) |
| Finance | RECONCILIATION_ONLY | NONE (internal transfers never income/expense) |
| Payroll | PAYROLL_RECONCILIATION | NONE (detail sheets source, REKAP control total) |
| Invoice | REGISTER_ONLY | NONE (no AR journal unless `OPENING_AR` + Finance auth) |
| Receipt | HISTORY_ONLY | NONE (links invoice on exact match, never duplicates payment) |
| Sparepart master | direct | NONE (creates `Item` with `source=LEGACY_IMPORT`) |
| Sparepart movement | history | STOCK only when `allow_stock_posting` + cut-off |
| Stock card/opname/report | benchmark | NONE |

## Key rules

- REKAP/SUMMARY/SISA/REPORT sheets are `RECONCILE_ONLY` — never transactions (§64).
- Balance always from ledger (`CustomerDeposit` sum, `StockLedger` sum), never stored editable numbers.
- Personal clearing: `REKENING ALASEN → PERSONAL_CLEARING` (Sale → Clearing receivable → Settlement), never hardcode personal accounts.
- Overtime: `LEMBUR_PAGI/SIANG/PERTAMA/SORE/MALAM/HARI_LIBUR` recalculated via shift/break rules; legacy hours compared, not trusted.
- Kasbon → Employee Loan Receivable (`Dr Salary Payable / Cr Loan Receivable`).
- Traceability: `legacy_import_links` + `source_file/sheet/row/hash/legacy_reference` on every record.
- Idempotency: file SHA-256 + deterministic row fingerprints; re-import skips duplicates, modified workbooks show NEW/UNCHANGED/CHANGED.
- Safety: `ALLOW_ACCOUNTING_POSTING=false`, `ALLOW_STOCK_POSTING=false` by default; Finance auth + cut-off + period lock required. Posted effects reverse, never hard-delete.

## Commands

```
php artisan legacy:scan <file>
php artisan legacy:import --batch=<id> --dry-run
php artisan legacy:import --batch=<id>
php artisan legacy:import-sales|deposit|finance|payroll|spareparts|documents --batch=<id> [--dry-run]
php artisan legacy:reconcile <batch>
php artisan legacy:report <batch>
```

## UI

TOOLS → DATA MIGRATION → BFJ LEGACY IMPORT (`bfj.index`): dashboard, workbook inspector, sheet mapping, master mapping queue, validation preview, impact simulation, import progress, reconciliation, variance detail, final report.
TOOLS → DATA MIGRATION → MASTER MAPPING (`bfj.mapping`): Customers/Employees/Drivers/Vehicles/Equipment/Materials/Spareparts with EXACT/ALIAS/POSSIBLE/NEW states.
