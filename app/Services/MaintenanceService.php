<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\MaintenanceCost;
use App\Models\MaintenancePart;
use App\Models\ProductionBatch;
use App\Models\PurchaseOrder;
use App\Models\VendorBill;
use App\Models\WorkOrder;
use App\Services\SalesService as SalesServiceAlias;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /**
     * Issue spare part for WO: inventory out + maintenance cost.
     */
    public static function issuePart(MaintenancePart $part): void
    {
        DB::transaction(function () use ($part) {
            if ($part->issue_status === 'ISSUED') {
                throw new \DomainException('Sparepart sudah diterbitkan.');
            }
            $wo = $part->workOrder;
            $warehouseId = $part->warehouse_id;
            if (!$warehouseId) {
                throw new \DomainException('Warehouse sparepart belum ditentukan.');
            }

            $unitCost = (float) $part->unit_cost ?: (float) Item::find($part->item_id)->avg_cost;
            $ledger = StockService::move(
                $warehouseId,
                $part->item_id,
                'MAINTENANCE_USAGE',
                0,
                (float) $part->qty,
                $wo->company_id,
                $wo->site_id,
                $wo->id,
                'WORK_ORDER',
                $wo->number,
                $unitCost,
                now()->toDateString()
            );

            $part->unit_cost = $unitCost;
            $part->total_cost = round($unitCost * $part->qty, 2);
            $part->issue_status = 'ISSUED';
            $part->stock_ledger_id = $ledger->id;
            $part->save();

            MaintenanceCost::create([
                'work_order_id' => $wo->id,
                'cost_type' => 'PART',
                'amount' => $part->total_cost,
                'notes' => 'Pemakaian sparepart',
            ]);

            $wo->actual_cost = (float) $wo->costs()->sum('amount');
            $wo->save();

            // Journal: Dr Maintenance Expense, Cr Inventory
            AccountingService::post($wo->company_id, now()->toDateString(), [
                ['code' => AccountingService::map('MAINTENANCE_EXPENSE'), 'debit' => $part->total_cost, 'memo' => 'Sparepart WO ' . $wo->number],
                ['code' => AccountingService::map('INVENTORY_SPAREPART'), 'credit' => $part->total_cost, 'memo' => 'Pemakaian sparepart WO ' . $wo->number],
            ], 'MAINTENANCE_PART', $wo->id, $wo->number, 'Issue sparepart ' . $wo->number, 'MNT');

            AuditService::log('UPDATE', 'MAINTENANCE', $wo->id, WorkOrder::class, null, ['part_issued' => $part->item_id, 'qty' => $part->qty]);
        });
    }
}
