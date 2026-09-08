<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\StockLedger;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * PART 43 — inventory integrity audit:
 * negative stock, orphan movement, duplicate movement, reservation > on-hand.
 * Exit code 1 on any critical finding (CI gate).
 */
class InventoryAuditIntegrity extends Command
{
    protected $signature = 'inventory:audit-integrity';

    protected $description = 'Audit integritas inventory: stok negatif, movement yatim, movement duplikat, reservasi > on-hand';

    protected function printDetail(): bool
    {
        return in_array('-v', (array) ($_SERVER['argv'] ?? []), true) || in_array('--verbose', (array) ($_SERVER['argv'] ?? []), true);
    }

    public function handle(): int
    {
        $critical = 0;

        // 1. negative stock per warehouse+item
        $negatives = StockLedger::query()
            ->selectRaw('warehouse_id, item_id, SUM(qty_in) - SUM(qty_out) as balance')
            ->groupBy('warehouse_id', 'item_id')
            ->havingRaw('SUM(qty_in) - SUM(qty_out) < -0.0001')
            ->get();
        if ($negatives->isNotEmpty()) {
            $critical += $negatives->count();
            $this->error("STOK NEGATIF: {$negatives->count()} kombinasi gudang+item.");
            if ($this->printDetail()) {
                foreach ($negatives as $n) {
                    $this->line("  warehouse {$n->warehouse_id} item {$n->item_id} balance {$n->balance}");
                }
            }
        }

        // 2. orphan movement (item or warehouse missing)
        $orphans = StockLedger::query()
            ->leftJoin('items', 'items.id', '=', 'stock_ledger.item_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'stock_ledger.warehouse_id')
            ->whereNull('items.id')
            ->orWhereNull('warehouses.id')
            ->count('stock_ledger.id');
        if ($orphans > 0) {
            $critical += $orphans;
            $this->error("MOVEMENT YATIM: {$orphans} baris stock_ledger tanpa item/warehouse.");
        }

        // 3. duplicate movement: same ref+item+warehouse+type+qty+date more than once
        $dupes = StockLedger::query()
            ->selectRaw('ref_type, ref_id, ref_number, item_id, warehouse_id, movement_type, qty_in, qty_out, trx_date, COUNT(*) as c')
            ->whereNotNull('ref_number')
            ->groupBy('ref_type', 'ref_id', 'ref_number', 'item_id', 'warehouse_id', 'movement_type', 'qty_in', 'qty_out', 'trx_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($dupes->isNotEmpty()) {
            $critical += $dupes->count();
            $this->error("MOVEMENT DUPLIKAT: {$dupes->count()} grup movement identik pada referensi yang sama.");
            if ($this->printDetail()) {
                foreach ($dupes as $d) {
                    $this->line("  {$d->movement_type} ref {$d->ref_number} item {$d->item_id} wh {$d->warehouse_id} in {$d->qty_in} out {$d->qty_out} @ {$d->trx_date} × {$d->c}");
                }
            }
        }

        // 4. reservation > on-hand
        $overReserve = 0;
        $reservations = StockReservation::where('status', 'RESERVED')->get();
        foreach ($reservations->groupBy(fn ($r) => $r->warehouse_id.'-'.$r->item_id) as $group) {
            $first = $group->first();
            $onHand = (float) StockLedger::where('warehouse_id', $first->warehouse_id)->where('item_id', $first->item_id)
                ->sum(DB::raw('qty_in - qty_out'));
            $reserved = (float) $group->sum(fn ($r) => max(0, (float) $r->qty - (float) $r->returned_qty));
            if ($reserved > $onHand + 0.0001) {
                $overReserve++;
                if ($this->printDetail()) {
                    $this->line("  wh {$first->warehouse_id} item {$first->item_id}: reserved {$reserved} > on_hand {$onHand}");
                }
            }
        }
        if ($overReserve > 0) {
            $critical += $overReserve;
            $this->error("RESERVASI > ON-HAND: {$overReserve} kombinasi gudang+item.");
        }

        // 5. valuation sanity: negative avg_cost
        $negCost = Item::where('avg_cost', '<', 0)->count();
        if ($negCost > 0) {
            $critical += $negCost;
            $this->error("AVG COST NEGATIF: {$negCost} item.");
        }

        $totalItems = Item::count();
        $totalMoves = StockLedger::count();
        $totalWh = Warehouse::count();

        if ($critical === 0) {
            $this->info("INVENTORY AUDIT PASS — {$totalItems} item, {$totalWh} gudang, {$totalMoves} movement. Stok negatif: 0 · yatim: 0 · duplikat: 0 · over-reserve: 0.");

            return self::SUCCESS;
        }
        $this->error("INVENTORY AUDIT FAILED — {$critical} temuan kritis.");

        return self::FAILURE;
    }
}
