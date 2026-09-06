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

class ProductionService
{
    /**
     * Post production batch:
     * - consume raw input from stockpile
     * - add net output to finished goods
     * - scrap recorded (optionally into scrap warehouse)
     * - journal: Dr FG Inventory, Cr WIP/Raw Inventory (at input cost)
     */
    public static function post(ProductionBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            if ($batch->status === 'POSTED') {
                throw new \DomainException('Batch sudah diposting.');
            }
            if ($batch->status !== 'APPROVED') {
                throw new \DomainException('Batch harus APPROVED sebelum posting.');
            }

            $totalInputCost = 0.0;
            foreach ($batch->inputs as $input) {
                $wh = $input->warehouse_id;
                if (!$wh) {
                    $crusher = \App\Models\Crusher::find($batch->crusher_id);
                    $wh = $crusher?->warehouse_id;
                }
                if (!$wh) {
                    throw new \DomainException('Warehouse sumber input tidak dikonfigurasi.');
                }
                $ledger = StockService::move(
                    $wh,
                    $input->item_id,
                    'PRODUCTION',
                    0,
                    (float) $input->tonnage,
                    $batch->company_id,
                    $batch->site_id,
                    $batch->id,
                    'PRODUCTION_BATCH',
                    $batch->number,
                    null,
                    $batch->date->toDateString()
                );
                $totalInputCost += (float) $ledger->total_cost;
            }

            $outputCostPerTon = $batch->net_output > 0 ? $totalInputCost / $batch->net_output : 0;

            foreach ($batch->outputs as $output) {
                $wh = $output->warehouse_id;
                if (!$wh) {
                    $crusher = \App\Models\Crusher::find($batch->crusher_id);
                    $wh = $crusher?->warehouse_id;
                }
                if (!$wh) {
                    throw new \DomainException('Warehouse output tidak dikonfigurasi.');
                }
                StockService::move(
                    $wh,
                    $output->item_id,
                    'PRODUCTION',
                    (float) $output->net_tonnage,
                    0,
                    $batch->company_id,
                    $batch->site_id,
                    $batch->id,
                    'PRODUCTION_BATCH',
                    $batch->number,
                    $outputCostPerTon,
                    $batch->date->toDateString()
                );
                // cermin ke stockpile yang terhubung gudang+item (no-op bila tidak ada pile)
                StockpileService::moveForWarehouse(
                    $wh,
                    $output->item_id,
                    'PRODUCTION_IN',
                    (float) $output->net_tonnage,
                    0,
                    $batch->id,
                    'PRODUCTION_BATCH',
                    $batch->number,
                    $batch->date->toDateString(),
                    'Output crusher ' . $batch->number
                );
            }

            foreach ($batch->scraps as $scrap) {
                if ($scrap->warehouse_id && $scrap->item_id) {
                    StockService::move(
                        $scrap->warehouse_id,
                        $scrap->item_id,
                        'SCRAP',
                        (float) $scrap->tonnage,
                        0,
                        $batch->company_id,
                        $batch->site_id,
                        $batch->id,
                        'PRODUCTION_BATCH',
                        $batch->number,
                        null,
                        $batch->date->toDateString()
                    );
                }
            }

            // Journal: Dr FG, Cr Raw/WIP at input cost (loss absorbed into FG cost)
            if ($totalInputCost > 0 && $batch->net_output > 0) {
                $fgValue = round($outputCostPerTon * $batch->net_output, 2);
                $journal = AccountingService::post($batch->company_id, $batch->date->toDateString(), [
                    ['code' => AccountingService::map('INVENTORY_FG'), 'debit' => $fgValue, 'memo' => 'Hasil produksi ' . $batch->number],
                    ['code' => AccountingService::map('INVENTORY_RAW'), 'credit' => $fgValue, 'memo' => 'Konsumsi bahan baku ' . $batch->number],
                ], 'PRODUCTION', $batch->id, $batch->number, 'Posting produksi ' . $batch->number, 'PRD');

                $batch->notes = trim(($batch->notes ? $batch->notes . ' ' : '') . '[journal:' . $journal->number . ']');
            }

            $batch->status = 'POSTED';
            $batch->posted_by = auth()->id();
            $batch->posted_at = now();
            $batch->save();

            AuditService::log('POST', 'PRODUCTION', $batch->id, ProductionBatch::class, null, ['batch' => $batch->number, 'net' => $batch->net_output]);
        });
    }
}
