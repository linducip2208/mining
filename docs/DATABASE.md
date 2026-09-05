# Skema Database

124 tabel, MySQL 8 / InnoDB. Semua uang `DECIMAL(20,2)`, berat `DECIMAL(20,4)` — tanpa float.

## Migration Files

| File | Tabel Utama |
|---|---|
| `0001_01_01_000000_create_users_table` | users (username, status, failed_login_count, lockout, force_password_reset, last_login), password_reset_tokens, sessions |
| `2026_09_05_103812_create_core_tables` | companies, branches, sites, divisions, departments, cost_centers, roles, permissions, permission_role, role_user (scope + company/site), menus, menu_role, approval_workflows, approval_steps, approval_requests, approval_actions, approval_delegations, audit_logs, login_history, notifications, document_numberings, settings |
| `2026_09_05_103920_create_hr_tables` | shifts, employees, attendances (unique employee+date), leaves, overtimes, payroll_components, employee_payroll_rules, payroll_runs, payroll_details (JSON komponen), operator_incentives |
| `2026_09_05_103923_create_master_data_tables` | payment_terms, units, item_categories, items (min_stock, reorder_point, avg_cost), warehouses, pits, crushers, weighbridges, weighbridge_calibrations, equipment, equipment_assignments, customers, suppliers, price_lists (+group), price_list_items, price_history, price_variances |
| `2026_09_05_104110_create_mining_tables` | mining_activities, haulings, vehicles, mining_productions, mining_production_details, weighbridge_tickets |
| `2026_09_05_104111_create_inventory_tables` | production_batches, production_inputs, production_outputs, production_losses, production_scraps, **stock_ledger** (append-only), stock_transfers, stock_transfer_items, stock_adjustments, stock_adjustment_items, stock_reservations |
| `2026_09_05_104112_create_procurement_tables` | purchase_requests, purchase_request_items, rfqs, rfq_suppliers, supplier_quotations, supplier_quotation_items, purchase_orders, purchase_order_items, goods_receipts, goods_receipt_items, vendor_bills, vendor_bill_items |
| `2026_09_05_104301_create_sales_tables` | sales_orders, sales_order_items, delivery_orders, delivery_order_items, invoices, invoice_items, payments, payment_allocations, **customer_deposits** (ledger) |
| `2026_09_05_104302_create_maintenance_tables` | assets, maintenance_schedules, maintenance_requests, work_orders, work_order_tasks, technician_assignments, maintenance_parts, downtimes, maintenance_costs, chart_of_accounts, cash_accounts, fiscal_periods, journal_entries, journal_lines, accounting_mappings, tax_codes, tax_transactions, reconciliations, bank_transactions |
| `2026_09_05_104450_create_document_csr_tables` | documents, document_versions, document_downloads, letter_sequences, csr_programs, csr_activities, csr_expenses, csr_documents, alert_rules |

## Tabel Inti (Source of Truth)

### stock_ledger (append-only, tanpa updated_at)
```sql
company_id, site_id, warehouse_id, item_id, trx_date,
movement_type ENUM(OPENING, PURCHASE, PRODUCTION, SALE, TRANSFER_IN, TRANSFER_OUT,
                   ISSUE, RETURN, ADJUSTMENT_PLUS, ADJUSTMENT_MINUS, SCRAP, MAINTENANCE_USAGE),
qty_in DECIMAL(20,4), qty_out DECIMAL(20,4),
unit_cost DECIMAL(20,2), total_cost DECIMAL(20,2),
ref_type, ref_id, ref_number, created_by
-- INDEX (warehouse_id, item_id, trx_date)
```
Saldo: `SUM(qty_in) - SUM(qty_out)` per warehouse+item.

### journal_entries + journal_lines
```sql
journal_entries: number UNIQUE, company_id, journal_date, period, source_type, source_id,
                 total_debit, total_credit, status(POSTED/VOID), is_reversal, reversal_of_id
journal_lines:   journal_entry_id FK, chart_of_account_id FK, site_id, cost_center_id,
                 debit DECIMAL(20,2), credit DECIMAL(20,2)
```
Invariant: `SUM(debit) = SUM(credit)` per entry — di-enforce `AccountingService`.

### customer_deposits (ledger)
```sql
customer_id, deposit_date, movement_type ENUM(DEPOSIT_IN, DEPOSIT_USED, DEPOSIT_REFUND),
amount DECIMAL(20,2), ref_type, ref_id, ref_number, cash_account_id, journal_entry_id
```
Saldo: `SUM(DEPOSIT_IN) − SUM(DEPOSIT_USED + DEPOSIT_REFUND)`.

## Konvensi Audit Metadata

Tabel master & transaksi membawa: `created_by`, `updated_by`, `approved_by`, `posted_by`, `timestamps`, `soft_deletes` (master). Semua aksi transaksional juga tercatat di `audit_logs` (user, action, module, old/new values, IP, user agent, reason).
