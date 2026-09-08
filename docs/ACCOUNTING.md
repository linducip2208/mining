# Akuntansi & Accounting Mapping

## Prinsip

1. **Double-entry wajib**: `SUM(debit) = SUM(credit)` — jurnal tidak balance **ditolak** (DomainException).
2. **Posted journal immutable** — koreksi hanya via **reversal** (jurnal cermin otomatis, `reversal_of_id` tercatat, tidak bisa double-reverse).
3. **Fiscal period guard**: posting ke periode CLOSED ditolak.
4. **Tidak ada hardcoded COA** — semua via `accounting_mappings` (code → chart_of_accounts.code). Tambah/ubah mapping di DB tanpa ubah kode.

## Mappings Aktif (33)

| Mapping Code | COA | Fungsi |
|---|---|---|
| CASH_MAIN | 1-1000 Kas | Kas default |
| BANK_MAIN | 1-1100 Bank | Bank default |
| AR_TRADE | 1-1200 Piutang Usaha | Faktur & pembayaran |
| INVENTORY_FG | 1-1300 Persediaan FG | Output produksi |
| INVENTORY_RAW | 1-1310 Persediaan Bahan Baku | Konsumsi produksi |
| INVENTORY_SPAREPART | 1-1320 Persediaan Sparepart | Issue WO |
| INVENTORY_FUEL | 1-1330 Persediaan BBM | Fuel receipt/issue |
| INVENTORY_GENERAL | 1-1310 | Vendor bill item |
| ACCUM_DEP | 1-1590 | Akumulasi penyusutan |
| CUSTOMER_DEPOSIT | 1-1700 | Deposit ledger |
| LOAN_RECEIVABLE | 1-1450 Pinjaman Karyawan | Potongan pinjaman karyawan |
| FIXED_ASSET | 1-1500 | Perolehan aset |
| AP_TRADE | 2-1000 Hutang Usaha | Vendor bill & pembayaran |
| SALARY_PAYABLE | 2-1100 | Payroll |
| TAX_PPN_OUT | 2-1200 | PPN keluaran |
| TAX_PPN_IN | 1-1400 Uang Muka PPN Masukan | Vendor bill |
| TAX_PPH21_PAYABLE | 2-1210 | Payroll (komponen PPh21) |
| BPJS_HEALTH_PAYABLE | 2-1220 | Payroll (komponen BPJS Kesehatan) |
| BPJS_EMPLOYMENT_PAYABLE | 2-1230 | Payroll (komponen BPJS Ketenagakerjaan) |
| OTHER_PAYROLL_PAYABLE | 2-1240 | Payroll (potongan lain-lain) |
| SALES_REVENUE | 4-1000 | Faktur penjualan |
| OTHER_REVENUE | 4-2000 | Pendapatan lain |
| VARIANCE_REVENUE | 4-3000 | Selisih harga masuk |
| COGS | 5-1000 | HPP delivery (Dr COGS / Cr Inventory) |
| SALARY_EXPENSE | 5-2000 | Payroll |
| INCENTIVE_EXPENSE | 5-2100 | Insentif operator |
| OVERTIME_EXPENSE | 5-2200 | Beban lembur (opsional, split dari salary) |
| MAINTENANCE_EXPENSE | 5-3000 | Issue sparepart |
| FUEL_EXPENSE | 5-3100 | Issue BBM |
| ADMIN_EXPENSE | 5-4000 | Vendor bill non-item |
| VARIANCE_EXPENSE | 5-4100 | Selisih harga keluar |
| DEPRECIATION_EXPENSE | 5-5000 | Penyusutan |
| CSR_EXPENSE | 5-6000 | Beban CSR |
| TAX_EXPENSE | 5-7000 | Pajak |
| OPENING_BALANCE_EQUITY | 3-3000 | Saldo awal (import opening AR/payment/stok) |

## Jurnal Otomatis per Transaksi

| Transaksi | Debit | Kredit |
|---|---|---|
| **Faktur Penjualan** | AR_TRADE (total) | SALES_REVENUE (subtotal), TAX_PPN_OUT (PPN) |
| **Pembayaran Customer** | CASH_MAIN/Bank | AR_TRADE |
| **HPP Delivery (COGS)** | COGS @moving-avg | INVENTORY_FG / INVENTORY_RAW / INVENTORY_GENERAL (per tipe item) |
| **Deposit Masuk** | Kas/Bank | CUSTOMER_DEPOSIT |
| **Refund Deposit** | CUSTOMER_DEPOSIT | Kas/Bank |
| **Alokasi Deposit ke Faktur** | CUSTOMER_DEPOSIT | AR_TRADE |
| **Vendor Bill (item)** | INVENTORY_GENERAL, TAX_PPN_IN | AP_TRADE |
| **Vendor Bill (non-item)** | ADMIN_EXPENSE | (gabung di atas) |
| **Pembayaran Supplier** | AP_TRADE | Kas/Bank |
| **Posting Produksi** | INVENTORY_FG | INVENTORY_RAW (nilai = biaya input, loss terserap ke FG) |
| **Issue Sparepart WO** | MAINTENANCE_EXPENSE | INVENTORY_SPAREPART |
| **Payroll Post** | SALARY_EXPENSE (+ OVERTIME_EXPENSE bila mapping tersedia) | TAX_PPH21_PAYABLE, BPJS_HEALTH_PAYABLE, BPJS_EMPLOYMENT_PAYABLE, LOAN_RECEIVABLE, OTHER_PAYROLL_PAYABLE (per komponen), SALARY_PAYABLE (neto) |
| **Payroll Pay** | SALARY_PAYABLE | Kas/Bank |
| **Beban CSR** | CSR_EXPENSE | CASH_MAIN |
| **Opening Balance (import)** | AR_TRADE / CASH_MAIN / INVENTORY_* | OPENING_BALANCE_EQUITY |

### COGS zero-cost policy

Delivery dengan `avg_cost = 0` mengikuti setting `inventory.cogs_zero_cost_policy`:
`BLOCK` (delivery ditolak — stok wajib punya biaya perolehan), `WARN` (default:
delivery jalan, audit WARNING `COGS_ZERO_COST` tercatat, tanpa jurnal nol),
`ALLOW` (diam, untuk migrasi data).

## Moving Average Cost

Saat `qty_in` dengan unit_cost, harga rata-rata bergerak diperbarui:
```
avg_cost_baru = (qty_lama × avg_cost_lama + qty_in × unit_cost) / (qty_lama + qty_in)
```
Nilai FG hasil produksi = total biaya input ÷ net_output (loss terserap ke beban pokok FG).

## Laporan Keuangan

- **Neraca Saldo**: per akun D & C, indikator BALANCE
- **Buku Besar**: kartu per akun dengan saldo berjalan
- **Laba Rugi**: revenue − expense per periode
- **Neraca**: Aset = Kewajiban + Ekuitas + Laba Berjalan (indikator balance)
- **AR/AP Aging**: bucket 0 / 1-30 / 31-60 / 61-90 / >90 hari

## Pajak (Configurable)

`tax_codes`: PPN11, PPN12, PPN_IN, PBB, PPH21 — **tarif di database, bukan hardcoded** (aturan bisa berubah). Setiap faktur & vendor bill mencatat `tax_transactions` (tax_base, tax_amount, period, kind) untuk rekap PPN keluaran/masukan & PBB/SPT manual.
