# BFJ Cutover Plan

Status: DRAFT — acceptance is FAIL / NOT_READY (see `docs/BFJ_FINAL_EXECUTION_REPORT.md`).
Do not cut over until blockers (725 unresolved masters, 81 critical rows) are resolved and acceptance re-runs green.

## Cutoff strategy

| Domain | Legacy handling | Cutover rule |
|---|---|---|
| Sales | HISTORY_ONLY (detail sheets import; REKAP reconcile-only, never double-imported) | Cutoff date T: sales ≤ T stay in legacy reconciliation; sales > T entered natively in ERP |
| Deposit | HISTORY_ONLY, money vs material-credit kept separate | Verified remaining balance at T becomes ERP deposit opening (via `DEPOSIT_IN` + audit note); consumption after T consumes ERP ledger |
| Finance | RECONCILIATION_ONLY (opening + inflow − outflow = closing benchmark) | Cash/bank opening balances at T posted as opening journals; no legacy transaction re-posted |
| Payroll | PAYROLL_RECONCILIATION (REKAP = benchmark) | Open payroll (unpaid net at T) posted as SALARY_PAYABLE opening; payroll > T calculated natively |
| Invoice/Receipt | REGISTER_ONLY / HISTORY_ONLY | Outstanding AR at T becomes opening AR journals; new invoices native |
| Sparepart | HISTORY/RECONCILIATION (STOK LAMA = opening) | Physical opname at T becomes stock OPENING movements at moving-average cost; post-T movements native |
| Stock/accounting live posting | OFF by default | Enabled only per-batch with Finance authorization + `confirm_impact` |

## Sequence

1. Freeze legacy entry at cutoff T (communicate to BFJ operators).
2. Run `bfj:acceptance-run` with `--execute-history` on final file set; resolve ALL unresolved masters (POSSIBLE never auto-links financial/stock masters — human decision required per row).
3. Post opening balances (stock / AR / AP / cash / deposit / payroll payable) with period = T, each backed by its reconciliation report row.
4. Snapshot: full database backup + `storage/app/documents` copy before first native posting.
5. First native postings (> T) run in a pilot site for 1 reconciliation cycle; compare control totals (sales volume, cash movement, payroll net) against legacy parallel run.
6. Controlled cutover only when: acceptance PASS, inventory + accounting integrity PASS, pilot variance = 0 unexplained.

## Rollback

- Pre-cutover snapshot restores the database to T.
- Opening journals reversed via `AccountingService::reverse` (never edited).
- BFJ history rows roll back only for non-posted effects (`rollback` refuses posted rows — they require reversal).
