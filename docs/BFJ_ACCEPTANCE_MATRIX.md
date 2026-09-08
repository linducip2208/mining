# BFJ Acceptance Matrix

| Source file | Source sheet | Expected | Parsed | Valid | Warn | Error | Matched | Imported | Reconciled | Variance | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Penjualan September 2026.xlsx | daily sheets | >0 | staging | READY/WARNING | amount variance | invalid date | masters queued | HISTORY_ONLY | vs REKAP | volume+value | PASS_WITH_WARNING if explained |
| Penjualan September 2026.xlsx | REKAP BFJ/MAA/RITEL/DEPOSIT | benchmark | RECONCILE_ONLY | — | — | — | — | 0 (never transactions) | MATCH/VARIANCE | 0 unexplained | PASS |
| DEPOSIT MATERIAL AGUSTUS 2026.xlsx | customer sheets | >0 | staging | READY | — | — | customers queued | HISTORY_ONLY | vs SISA DEPOSIT | 0 unexplained | PASS |
| Laporan Keuangan Agustus 2026.xlsx | cash-flow sheets | >0 | staging | READY | flow split | — | accounts queued | RECONCILIATION_ONLY | OPEN+IN−OUT=CLOSE | 0 unexplained | PASS |
| GAJI KARYAWAN AGUSTUS 2026.xlsx | employee sheets | >0 | staging | READY | hours variance | — | employees queued | RECONCILIATION | vs REKAP | 0 unexplained | PASS |
| Correspondence register | letter rows | >0 | parsed tokens | READY | pattern variance | invalid date | — | preserved numbers | — | — | PASS |
| Invoice register | invoice rows | >0 | REGISTER_ONLY | READY | — | missing total | customers queued | DRAFT, no AR post | — | — | PASS |
| Receipt register | receipt rows | >0 | HISTORY_ONLY | READY | — | missing nominal | invoices linked | no duplicate payment | — | — | PASS |
| Sparepart warehouse | master/opening/issue | >0 | masters+movements | READY | note flags | missing qty | items/equipment queued | master direct; movement gated | vs kartu/opname/report | 0 unexplained | PASS |

Status: PASS / PASS_WITH_WARNING / FAIL. Critical unexplained variance must be 0 before CLOSE.
Cut-offs (sales/stock/accounting/payroll/deposit) configured per batch; closed periods block posting unless audited migration override.
