# Sparepart Workflow — Mining ERP

Sparepart **tidak punya tabel paralel**: master = `items` (type `SPAREPART`),
saldo = `stock_ledger` (satu-satunya source of truth). Kolom tambahan di items:
brand, part_number, alt_part_number, storage_location_id (warehouse→zone→rack→bin),
min/max stock, reorder point, preferred_supplier_id, avg_cost (moving average),
last_purchase_price.

## Chain: Reserve → Issue → Return → Consumed

```
WORK ORDER (APPROVED)
→ RESERVE      SparepartService::reserve (lock item → cek on_hand − reserved → buat/jumlah baris reservasi)
→ ISSUE        MaintenanceService::issuePart
               StockService::move(MAINTENANCE_USAGE) — unit_cost = snapshot saat issue
               reservasi: issued_qty += qty, issued_unit_cost = snapshot (immutable)
               maintenance_parts: issued, cost, ledger id, jurnal
→ STOCK OUT    ledger qty_out @unit_cost snapshot (mahkota tak berubah walau avg bergerak)
→ RETURN       SparepartController::returnStock
               HARUS ≤ issued − returned; unit_cost return = issued_unit_cost ASLI
               ledger RETURN qty_in @harga issue asli (tidak dinilai ulang di avg baru)
               reservasi & parts: returned_qty += qty; consumed_qty = issued − returned
→ MAINTENANCE COST  MaintenanceCost (cost_type PART) + jurnal
→ EQUIPMENT COST    lifetime cost per unit (fleet/lifetime-cost)
→ ACCOUNTING        Dr MAINTENANCE_EXPENSE / Cr INVENTORY_SPAREPART
```

**Ketersediaan** = on_hand − reserved; reserved = Σ(qty − issued) reservasi
RESERVED (barang yang sudah di-return otomatis bebas lagi karena masuk ledger
sebagai qty_in). Pengecekan dilakukan **di dalam lock item** sehingga dua
reservasi konkuren tidak bisa sama-sama lolos.

**Concurrency & guard**

- Issue dua kali ditolak (issue_status ISSUED).
- Over-issue melewati saldo ditolak (`inventory.allow_negative_stock` = false).
- Return melebihi issued ditolak.
- Reservasi konkuren untuk dua WO: yang kedua gagal bila stok tidak cukup.

**Jurnal** — mapping konfiguratif, TIDAK hardcoded:
`AccountingService::map('MAINTENANCE_EXPENSE')` / `map('INVENTORY_SPAREPART')`.
Mapping belum dikonfigurasi → posting **gagal** (block), tidak pernah skip diam-diam.

## Receipt / Issue / Opname langsung (tanpa WO)

- **Barang masuk** (`sparepart/receipt`): kondisi BAIK/RUSAK/REPAIR masuk ledger
  `SPAREPART_IN` @unit_cost (moving average ter-update); REJECTED tidak masuk stok.
- **Barang keluar** (`sparepart/issue`): alasan MAINTENANCE/TRANSFER/CONSUMPTION/
  RETURN_TO_VENDOR/ADJUSTMENT/OTHER → ledger `SPAREPART_OUT`. Lewat work_order_id
  → masuk rantai WO di atas (MaintenancePart dibuat + di-issue).
- **Opname**: StockAdjustment type OPNAME, status COUNTING → REVIEW → posting:
  selisih masuk ledger `OPNAME_ADJUSTMENT` + jurnal Dr/Cr VARIANCE.

## Valuasi

- Moving average di-update pada setiap qty_in berbiaya:
  `avg = (qty_lama × avg_lama + qty_in × unit_cost) / qty_total`.
- Issue & WO memakai **snapshot saat issue**; return memakai **snapshot issue
  asli** — tidak ada revaluasi pada return (Part 11 audit).
- Kartu stok & nilai dihitung on-the-fly dari ledger (tidak ada saldo tersimpan
  yang bisa drift).

## Perintah pendukung

| Command | Fungsi |
|---|---|
| `php artisan inventory:audit-integrity` | stok negatif, movement yatim/duplikat, over-reserve (exit 1 saat kritis) |
| `php artisan inventory:reconcile-legacy` | saldo legacy (opening import) vs saldo ERP dari ledger |
| `php artisan maintenance:generate-wo` | WO draft dari jadwal jatuh tempo (anti duplikat) |
