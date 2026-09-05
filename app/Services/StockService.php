<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Setting;
use App\Models\StockLedger;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for stock: STOCK MOVEMENT LEDGER.
 * Never update a balance column directly; always call move().
 */
class StockService
{
    public const NEGATIVE_ALLOWED_SETTING = 'inventory.allow_negative_stock';

    public static function move(
        int $warehouseId,
        int $itemId,
        string $movementType,
        float $qtyIn = 0,
        float $qtyOut = 0,
        ?int $companyId = null,
        ?int $siteId = null,
        ?int $refId = null,
        ?string $refType = null,
        ?string $refNumber = null,
        ?float $unitCost = null,
        ?string $trxDate = null,
        ?string $notes = null
    ): StockLedger {
        return DB::transaction(function () use ($warehouseId, $itemId, $movementType, $qtyIn, $qtyOut, $companyId, $siteId, $refId, $refType, $refNumber, $unitCost, $trxDate, $notes) {
            $item = Item::lockForUpdate()->find($itemId);
            if (!$item) {
                throw new \InvalidArgumentException('Item tidak ditemukan.');
            }

            if ($qtyOut > 0) {
                $available = self::balance($warehouseId, $itemId);
                $reserved = StockReservation::where('warehouse_id', $warehouseId)
                    ->where('item_id', $itemId)
                    ->where('status', 'RESERVED')
                    ->sum('qty');
                if (!$companyId) {
                    $companyId = StockLedger::where('warehouse_id', $warehouseId)->value('company_id');
                }

                if ($available - $qtyOut < -0.0001) {
                    if (!filter_var(Setting::get(self::NEGATIVE_ALLOWED_SETTING, 'false'), FILTER_VALIDATE_BOOL)) {
                        throw new \DomainException("Stok tidak cukup untuk {$item->code} - {$item->name}. Tersedia: {$available}, Diminta: {$qtyOut}.");
                    }
                }
            }

            if ($unitCost === null && $qtyIn > 0) {
                $unitCost = $item->avg_cost ?: $item->standard_cost;
            }

            $ledger = StockLedger::create([
                'company_id' => $companyId ?? 1,
                'site_id' => $siteId,
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'trx_date' => $trxDate ?? now()->toDateString(),
                'movement_type' => $movementType,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'unit_cost' => $unitCost ?? 0,
                'total_cost' => $unitCost ? round($unitCost * max($qtyIn, $qtyOut), 2) : 0,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'ref_number' => $refNumber,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Moving average cost on inbound
            if ($qtyIn > 0 && $unitCost) {
                $oldQty = self::balance($warehouseId, $itemId) - $qtyIn;
                $oldValue = $oldQty * (float) $item->avg_cost;
                $newQty = $oldQty + $qtyIn;
                if ($newQty > 0) {
                    $item->avg_cost = round(($oldValue + ($qtyIn * $unitCost)) / $newQty, 2);
                    $item->save();
                }
            }

            return $ledger;
        });
    }

    public static function balance(int $warehouseId, int $itemId): float
    {
        return (float) StockLedger::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->selectRaw('COALESCE(SUM(qty_in),0) - COALESCE(SUM(qty_out),0) as bal')
            ->value('bal');
    }

    public static function balancesByItem(int $itemId, ?int $companyId = null): float
    {
        return (float) StockLedger::where('item_id', $itemId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('COALESCE(SUM(qty_in),0) - COALESCE(SUM(qty_out),0) as bal')
            ->value('bal');
    }

    public static function reserve(int $warehouseId, int $itemId, string $refType, $refId, ?string $refNumber, float $qty): StockReservation
    {
        $available = self::balance($warehouseId, $itemId)
            - StockReservation::where('warehouse_id', $warehouseId)->where('item_id', $itemId)->where('status', 'RESERVED')->sum('qty');
        if ($qty > $available + 0.0001) {
            throw new \DomainException('Stok tersedia tidak cukup untuk reservasi.');
        }
        return StockReservation::create([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'ref_type' => $refType,
            'ref_id' => $refId,
            'ref_number' => $refNumber,
            'qty' => $qty,
            'status' => 'RESERVED',
        ]);
    }

    public static function releaseReservations(string $refType, $refId): void
    {
        StockReservation::where('ref_type', $refType)->where('ref_id', $refId)->update(['status' => 'RELEASED']);
    }

    public static function consumeReservations(string $refType, $refId): void
    {
        StockReservation::where('ref_type', $refType)->where('ref_id', $refId)->update(['status' => 'CONSUMED']);
    }
}
