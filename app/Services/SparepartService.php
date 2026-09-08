<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockLedger;
use App\Models\StockReservation;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

/**
 * Sparepart warehouse administration on top of the stock ledger.
 * Never maintains a parallel balance — StockLedger stays the source of truth.
 */
final class SparepartService
{
    public static function spareparts()
    {
        return Item::where('type', 'SPAREPART');
    }

    public static function onHand(int $warehouseId, int $itemId): float
    {
        return (float) StockLedger::where('warehouse_id', $warehouseId)->where('item_id', $itemId)->sum(DB::raw('qty_in - qty_out'));
    }

    public static function reserved(int $warehouseId, int $itemId): float
    {
        // outstanding reservation = qty - issued (CASE for cross-DB support).
        // Returned goods are already back in on_hand via the ledger — they are
        // free stock again, so they must NOT be re-subtracted here.
        return (float) StockReservation::where('warehouse_id', $warehouseId)->where('item_id', $itemId)->where('status', 'RESERVED')
            ->sum(DB::raw('CASE WHEN issued_qty > qty THEN 0 ELSE qty - issued_qty END'));
    }

    public static function available(int $warehouseId, int $itemId): float
    {
        return self::onHand($warehouseId, $itemId) - self::reserved($warehouseId, $itemId);
    }

    public static function stockStatus(Item $item, float $available): string
    {
        if ($available <= 0) {
            return 'OUT_OF_STOCK';
        }
        $reorder = (float) ($item->reorder_point ?? 0);
        $min = (float) ($item->min_stock ?? 0);
        if ($reorder > 0 && $available <= $reorder) {
            return 'CRITICAL';
        }
        if ($min > 0 && $available <= $min) {
            return 'LOW';
        }

        return 'NORMAL';
    }

    /**
     * Concurrency-safe reservation (row lock on the item + balance check inside lock).
     * The availability check runs AFTER the lock so two concurrent reservations
     * for different WOs can never both pass against the same stock.
     */
    public static function reserve(int $warehouseId, int $itemId, WorkOrder $wo, float $qty): StockReservation
    {
        return DB::transaction(function () use ($warehouseId, $itemId, $wo, $qty) {
            Item::where('id', $itemId)->lockForUpdate()->first();

            $available = self::available($warehouseId, $itemId);
            if ($qty > $available + 0.0001) {
                throw new \DomainException('Stok tersedia tidak cukup untuk reservasi.');
            }
            $existing = StockReservation::where('warehouse_id', $warehouseId)->where('item_id', $itemId)
                ->where('ref_type', 'WORK_ORDER')->where('ref_id', $wo->id)->where('status', 'RESERVED')
                ->lockForUpdate()->first();
            if ($existing) {
                $existing->increment('qty', $qty);

                return $existing->fresh();
            }

            return StockReservation::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'ref_type' => 'WORK_ORDER',
                'ref_id' => $wo->id,
                'ref_number' => $wo->number,
                'qty' => $qty,
                'status' => 'RESERVED',
            ]);
        });
    }

    /**
     * Mark part of a reservation as issued. Tracks the immutable issue unit
     * cost (current moving average at issue time) so returns can be valued
     * at the original issue cost, never re-valued at today's average.
     */
    public static function markIssued(StockReservation $reservation, float $qty, float $unitCost): void
    {
        $reservation->increment('issued_qty', $qty);
        if ($reservation->issued_unit_cost === null) {
            $reservation->update(['issued_unit_cost' => $unitCost]);
        }
    }

    /**
     * Return issued qty back to stock at the ORIGINAL issue unit cost.
     * consumed_qty = issued_qty - returned_qty (kept consistent here).
     */
    public static function returnIssued(StockReservation $reservation, float $qty): float
    {
        $unitCost = (float) ($reservation->issued_unit_cost ?? 0);
        $reservation->increment('returned_qty', $qty);
        $reservation->refresh();
        $reservation->update(['consumed_qty' => max(0, (float) $reservation->issued_qty - (float) $reservation->returned_qty)]);

        return $unitCost;
    }

    public static function recommendedQty(Item $item, float $available): float
    {
        $max = $item->max_stock;
        if ($max === null) {
            $max = (float) ($item->reorder_point ?? 0) * 2;
        }

        return max(0, round((float) $max - $available, 4));
    }

    public static function fingerprint(array $parts): string
    {
        return hash('sha256', implode('|', array_map(fn ($p) => trim((string) $p), $parts)));
    }
}
