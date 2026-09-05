<?php

namespace App\Services;

use App\Models\PriceVariance;
use App\Models\Setting;

class PriceService
{
    /**
     * Resolve selling price: customer price > customer group > site price > standard price list.
     */
    public static function resolvePrice($customer, $siteId, $itemId): float
    {
        $lists = \App\Models\PriceList::query()
            ->where('company_id', $customer->company_id)
            ->where('status', 'APPROVED')
            ->whereDate('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now());
            });

        $candidates = [
            ['customer_id', $customer->id],
            ['group', $customer->group],
            ['site_id', $siteId],
        ];

        foreach ($candidates as [$field, $value]) {
            if (!$value) {
                continue;
            }
            $pl = (clone $lists)->where($field, $value)->first();
            if ($pl) {
                $price = $pl->items()->where('item_id', $itemId)->value('price');
                if ($price !== null) {
                    return (float) $price;
                }
            }
        }

        return (float) (\App\Models\Item::find($itemId)->standard_cost ?? 0);
    }

    /**
     * Record reference (retail) vs realization price variance.
     * Variance is informational only — accounting meaning is configured via mappings.
     */
    public static function recordVariance(string $module, $companyId, $itemId, float $referencePrice, float $realizationPrice, float $quantity, $transactionId = null, ?string $reason = null): ?PriceVariance
    {
        if ($referencePrice <= 0) {
            return null;
        }
        $variance = ($realizationPrice - $referencePrice) * $quantity;
        $type = $variance >= 0 ? 'FAVORABLE' : 'UNFAVORABLE';

        return PriceVariance::create([
            'number' => NumberingService::generate('PV'),
            'module' => $module,
            'transaction_id' => $transactionId,
            'item_id' => $itemId,
            'company_id' => $companyId,
            'reference_price' => $referencePrice,
            'realization_price' => $realizationPrice,
            'quantity' => $quantity,
            'variance' => $variance,
            'variance_percentage' => $referencePrice > 0 ? round(($realizationPrice - $referencePrice) / $referencePrice * 100, 2) : 0,
            'variance_type' => $type,
            'reason' => $reason,
            'approval_status' => 'PENDING',
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    public static function recordInvoiceVariance($invoice): void
    {
        foreach ($invoice->items as $item) {
            $itemMaster = \App\Models\Item::find($item->item_id);
            $retail = (float) Setting::get('sales.retail_price_' . $item->item_id, 0);
            if ($retail <= 0) {
                continue;
            }
            self::recordVariance('SALES', $invoice->company_id, $item->item_id, $retail, (float) $item->unit_price, (float) $item->qty, $invoice->id, 'Invoice ' . $invoice->number);
        }
    }
}
