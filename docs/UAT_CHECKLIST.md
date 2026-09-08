# UAT Checklist

Roles execute their scenarios on staging data that mirrors production masters. Each scenario: expected result, actual result, pass/fail, tester, date.

## Super Admin

- [ ] Create user + assign role + company/site scope → user sees only scoped data (cross-company URL access returns 403)
- [ ] Change a secured setting → audit log records actor, before/after
- [ ] Run `inventory:audit-integrity` and `accounting:audit-integrity` → PASS

## Finance

- [ ] Post manual journal (balanced) → POSTED; unbalanced → rejected
- [ ] Reverse a posted journal → reversal posted, original untouched
- [ ] Post into CLOSED period → rejected
- [ ] Receive customer payment across 2 invoices → FIFO allocation, statuses PAID/PARTIALLY_PAID, balanced journal
- [ ] Overpay attempt → rejected; second payment completes outstanding
- [ ] Vendor bill: post, partial pay, overpay attempt rejected, void reverses journal
- [ ] Deposit in → allocate to invoice → refund → statement balance matches ledger walk

## HR / Payroll

- [ ] Attendance + approved overtime → payroll calculate uses MERGED_NON_DUPLICATE policy (no double count)
- [ ] Payroll approve → post → pay: three distinct journals, double-post rejected, double-pay rejected

## Warehouse

- [ ] Receive (GRN) → stock card shows IN at PO cost; over-receipt rejected
- [ ] Transfer between warehouses → both legs in ledger
- [ ] Opname → variance posted as adjustment after approval
- [ ] Reserved stock for a WO cannot be consumed by an unrelated issue

## Mining Ops / Weighbridge

- [ ] Dispatch trip: assign truck+driver, stamp phases in order, link ticket → tonnage from ticket only
- [ ] Duplicate truck+shift+date assignment → rejected; breakdown truck → rejected
- [ ] Weighbridge: first weigh → second weigh → net derived; override requires reason and is audited; POSTED ticket override rejected
- [ ] Same ticket cannot feed two DOs; HOLD from failed QC blocks DO completion until released

## Maintenance

- [ ] WO → request part → reserve → issue (stock OUT + maintenance journal) → return (stock IN at original cost + reversal journal + WO cost reduced)

## Sales

- [ ] SO approve → reserve → DO + weighbridge → complete (stock OUT + COGS journal) → invoice (no duplicate per SO) → payment → receipt print

## Manager / Approver

- [ ] Approval center shows pending items; approve/reject with note; delegation works; audit trail complete

## Cross-cutting

- [ ] Every tested screen renders at 390×844 (mobile) and 1366×768 (desktop) with no page-level horizontal scroll
- [ ] Print each document type to A4 and 80mm thermal → layout correct, numbers match screen
