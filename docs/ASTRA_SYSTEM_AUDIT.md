# ASTRA System Audit — Mining ERP

Baseline: commit `f898152` (branch `main`) · Laravel 13.30.1 · PHP 8.3.30 · MySQL · 443 app classes · 44 migrations · 344 views · CI (PHP/SQLite + MySQL + frontend).
Baseline validation: 355 tests PASS · `npm run build` PASS · migrations PASS · inventory + accounting integrity PASS.

This session hardened integrity/security/wiring and added 69 tests (424 total PASS). Business-flow verdicts below are evidence-based (service/controller/migration reads + test runs).

## Flow matrix (critical)

| Module | Source of truth | Stock effect | Accounting effect | Approval / scope | Test | Status |
|---|---|---|---|---|---|---|
| Dispatch / Hauling | `dispatch_trips` + phases | via ticket only | — | site scope on all actions; truck status + driver validated | StockIntegrity | WORKING |
| Weighbridge | `weighbridge_tickets` | net derived | via DO | override gated + audited; POSTED locked; net>0 on post | WeighbridgeGuard | WORKING |
| Stockpile | `stockpile_movements` | balance = SUM, negatives blocked | — | survey approve via approval center | existing | WORKING |
| Production / Crusher | `production_batches` + ledger | input OUT / FG IN | Dr FG / Cr Raw | yield ≤ input enforced; locked post | ProductionYield | WORKING |
| Quality / Lab | `qc_samples` + holds | — | — | HOLD blocks DO; release/override permissioned + audited | existing | WORKING |
| Sales (SO→DO→COGS→INV→PAY) | SO/DO/INV/Payment + allocations | OUT at avg + COGS | Dr COGS/Cr Inv; Dr AR/Cr Rev+PPN; Dr Cash/Cr AR | ticket locked in-txn; one active invoice per SO; allocation ≤ outstanding | SalesCogs/PaymentAllocation | WORKING |
| Customer deposit | `customer_deposits` append-only | — | Dr Cash/Cr Deposit; Dr Deposit/Cr AR; Dr Deposit/Cr Cash | locked balance check; signed adjustments audited | DepositLedger | WORKING |
| Procurement (PR→PO→GRN→Bill→Pay) | PO/GRN/Bill + ledger | IN at PO cost | Dr Inv/Exp+PPN-In/Cr AP; Dr AP/Cr Cash | cumulative over-bill blocked; pay only POSTED; over-order vs PR | ProcurementGuard | WORKING |
| Inventory | `stock_ledger` | balance = SUM; reservation enforced; company-validated | via source postings | data scope | StockIntegrity | WORKING |
| Sparepart → Maintenance | ledger + reservation chain | OUT/IN at issue-cost snapshot | Dr MaintExp/Cr SpareInv; return reversal posted | WO must be open to issue | SparepartReturnReversal | WORKING |
| Maintenance / WO | `work_orders` + costs | parts consumed = issued − returned | via mappings, missing blocks | due-WO generation idempotent | existing | WORKING |
| Fuel | `fuel_ledger` | per-tank SUM; negatives blocked under lock | Dr FuelInv/Cr AP; Dr FuelExp/Cr FuelInv | dip variance PENDING + alerted | FuelLifecycle | WORKING |
| Tire | `tires` + `tire_movements` | — | repair cost noted (not posted) | slot uniqueness app-level | TireLifecycle | PARTIAL (repair cost not posted; no DB unique on slot) |
| HR / Payroll | attendance + overtime + runs | — | Dr expenses/Cr deductions+payable; Dr payable/Cr cash | overtime MERGED_NON_DUPLICATE; locked post/pay | Payroll* | WORKING |
| Finance / double-entry | `journal_entries` | — | balance enforced; posted immutable; reversal only; closed period blocks | mapping-configured, no hardcoded COA | existing | WORKING |
| Budget | budgets + commitments + actuals | — | WARN/BLOCK + override permission | commitment on PR/PO | BudgetControl | WORKING |
| Contract | contracts + realizations | — | — | over-contract at SO/PO with override | existing | PARTIAL (DO/invoice-time + date scoping gaps) |
| HSE / Compliance | reports + actions + permits | — | — | severity notify; action verify before close | HseWorkflow | WORKING |
| Letters / numbering | letter register + `NumberingService` | — | — | row-locked, period reset | Letter* | WORKING |
| Print engine | PrintDocument/PrintJob + profiles | — | — | `*.print/*.pdf` permissions, audited | Print* | WORKING |
| BFJ migration | Bfj engine + legacy tables | HISTORY-only by default | RECONCILIATION-only by default | Finance auth + confirm for live effects | 56 Bfj tests | PARTIAL (acceptance FAIL: 725 unresolved masters, 81 critical rows) |
| RBAC / data scope | permissions + CheckPermission + scopes | — | — | route permission coverage; IDOR guard on bound models | DataScope/RBAC-partial | WORKING (open registration still a decision item) |

## Fixes applied this session

1. Ticket consumption locked inside DO transaction (no double-post race); one ticket → one DO enforced in service + unique DB index.
2. Weighbridge override blocked on POSTED/VOID/CANCELLED; zero-net override rejected; before/after audit; post requires net>0 under lock.
3. Dispatch: truck serviceability + driver ACTIVE + driver double-book validated; duplicate check works without shift; ticket cross-link to other trip/trip-company-site validated; stamp transactional + locked + audited; site scope on show/stamp/link/cancel/dashboard.
4. Stock: reservations enforced against unrelated OUT moves (owning ref exempt); warehouse↔company/site consistency on every move; OUT moves snapshot moving-average unit cost.
5. Production: impossible yield (>101% of input) and zero-input batches rejected; post locked against double-post race.
6. Deposit: allocate/refund transactional with per-customer lock; `DEPOSIT_ADJUSTMENT` (±, reasoned, audited); `kind` MONEY vs MATERIAL_CREDIT separated; migration adds `direction`/`kind` with backfill.
7. Procurement: cumulative over-bill blocked at post; pay requires POSTED/PARTIALLY_PAID + locked bill; GRN post locked; PO-vs-PR over-order guard; unique (supplier, supplier_invoice_no).
8. Maintenance: `issuePart` requires open WO; returns post Dr SparepartInv / Cr MaintExp reversal at original cost + PART_RETURN cost row + WO actual_cost recompute.
9. Quality: site-specific specs now resolve via source site.
10. Payroll: `post`/`pay` locked against double-post/double-pay races.
11. Security: `deposit.statement` + `documents.download` permissioned and scoped; `role.show` permissioned; `private` storage disk defined; unscoped transfer/document indexes scoped; HSE/Compliance uploads whitelisted; `document.download` permission seeded.
12. UX/data honesty: SEO hero workflow prop fixed (renders again); WhatsApp display number centralized; dashboard fake deltas/sparkline/synthesized fuel chart replaced with real aggregates (N/A where unavailable); missing `FUEL_DIP_VARIANCE` alert wired.
13. Tests: FuelLifecycle, TireLifecycle, BudgetControl, HseWorkflow, PaymentAllocation, DepositLedger, StockIntegrity, ProcurementGuard, ProductionYield, WeighbridgeGuard, SparepartReturnReversal (69 new tests).
14. BFJ: `bfj:acceptance-run` now also writes `docs/BFJ_FINAL_EXECUTION_REPORT.md` with real measured numbers; re-ran on real batches (6/6 files, 2351 rows) → FAIL/NOT_READY, honestly reported.

## Known remaining gaps (non-blocking for UAT, blocking for go-live where noted)

- Tire repair cost not posted to GL; slot uniqueness app-level only (no DB unique) — MEDIUM.
- Contract DO/invoice-time and date-scope enforcement — MEDIUM.
- BFJ acceptance blockers: 725 unresolved masters, 81 critical rows (mostly sparepart) — GO-LIVE BLOCKER for migration scope.
- Fuel `tankAvgCost` lifetime-average drift; dip shrinkage not posted as write-off — MEDIUM/LOW.
- Open self-registration route — explicit product decision required.
- `StockService` moving-average is global per item (not per warehouse) — documented limitation.
- 5 BFJ placeholder tests assert nothing — LOW.
