# BFJ Final Execution Report

Generated: 2026-09-08 23:42:25 · duration 6.1s · command `php artisan bfj:acceptance-run`

## Verdict

- Acceptance: **FAIL**
- Go-live: **NOT_READY**
- Files recognized: 6/6

## Batches executed

- batch #14 (Penjualan September 2026.xlsx, `764c4dd4e1ed…`, 11 sheets)
- batch #15 (DEPOSIT MATERIAL BULAN AGUSTUS 2026.xlsx, `731e0e159014…`, 13 sheets)
- batch #16 (Laporan Keuanga Bulan Agustus 2026 (1) (1).xlsx, `110704820543…`, 5 sheets)
- batch #17 (08_PERHITUNGAN GAJI KARYAWAN AGUSTUS 2026.xlsx, `aed762f1683a…`, 21 sheets)
- batch #18 (letters.csv, `f19ebb47ac79…`, 1 sheets)
- batch #19 (invoices.csv, `1ffd61fc7970…`, 1 sheets)
- batch #20 (receipts.csv, `713cea0ac1fd…`, 1 sheets)
- batch #21 (sp_master.csv, `5d7bd69d10eb…`, 1 sheets)
- batch #22 (sp_masuk.csv, `24e8fe301744…`, 1 sheets)
- batch #23 (sp_keluar.csv, `03a2e300872f…`, 1 sheets)
- batch #24 (sp_kartu.csv, `ec78810e135c…`, 1 sheets)
- batch #25 (sp_opname.csv, `fa22914fd0bd…`, 1 sheets)
- batch #26 (sp_laporan.csv, `b45e22b33fdf…`, 1 sheets)

## Rows by domain

| Domain | Rows | Valid | Warnings | Errors | Duplicates | Match | Variance | Unexplained | Critical |
|---|---|---|---|---|---|---|---|---|---|
| Sales | 277 | 49 | 0 | 0 | 0 | 40 | 13 | 0 | 0 |
| Deposit | 681 | 39 | 37 | 0 | 0 | 10 | 15 | 0 | 0 |
| Finance | 329 | 91 | 0 | 0 | 0 | 13 | 0 | 0 | 0 |
| Payroll | 816 | 234 | 0 | 2 | 0 | 51 | 7 | 0 | 2 |
| Documents | 28 | 0 | 0 | 2 | 0 | 0 | 0 | 0 | 2 |
| Spareparts | 220 | 19 | 0 | 79 | 1 | 15 | 2 | 0 | 79 |

## Master mapping

- Total: 727 · resolved: 2 · unresolved: 725

- EQUIPMENT / NEW_MASTER_REQUIRED: 2
- CUSTOMER / ALIAS_MATCH: 1
- CUSTOMER / RESOLVED: 1
- EMPLOYEE / POSSIBLE_MATCH: 6

## Integrity & quality gates

- Tests: PASS
- Inventory integrity: PASS
- Accounting integrity: PASS

## Blockers

- Unexplained variance rows: 0
- Critical error rows: 81
- Unresolved masters: 725

> Safety posture: sales/deposit HISTORY_ONLY, finance RECONCILIATION_ONLY, payroll PAYROLL_RECONCILIATION, invoice REGISTER_ONLY, receipt HISTORY_ONLY. No live stock/accounting posting without Finance authorization + explicit confirm flag.
