<?php

namespace App\Services;

use App\Models\CustomerContract;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceiptItem;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\SupplierContract;
use Illuminate\Support\Facades\DB;

/**
 * Contract realization is always computed live from transactions.
 * Over-contract sales orders are blocked unless the actor holds
 * the contract.override permission (special approval).
 */
class ContractService
{
    public static function activeCustomerContract(int $customerId, int $itemId, ?string $date = null): ?CustomerContract
    {
        $date = $date ?? now()->toDateString();
        return CustomerContract::where('customer_id', $customerId)
            ->where('item_id', $itemId)
            ->where('status', 'ACTIVE')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('id')
            ->first();
    }

    public static function customerRealization(CustomerContract $c): array
    {
        $ordered = (float) SalesOrder::where('customer_id', $c->customer_id)
            ->whereNotIn('status', ['DRAFT', 'REJECTED', 'CANCELLED'])
            ->whereHas('items', fn ($q) => $q->where('item_id', $c->item_id))
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_order_items.item_id', $c->item_id)
            ->sum('sales_order_items.qty');

        $delivered = (float) SalesOrder::where('customer_id', $c->customer_id)
            ->whereNotIn('status', ['DRAFT', 'REJECTED', 'CANCELLED'])
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_order_items.item_id', $c->item_id)
            ->sum('sales_order_items.qty_delivered');

        $invoiced = (float) Invoice::where('invoices.customer_id', $c->customer_id)
            ->whereNotIn('invoices.status', ['DRAFT', 'CANCELLED', 'VOID'])
            ->join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.item_id', $c->item_id)
            ->sum('invoice_items.qty');

        $contractQty = (float) $c->contract_qty;
        $price = (float) $c->price;

        return [
            'contract_qty' => $contractQty,
            'ordered_qty' => round($ordered, 4),
            'delivered_qty' => round($delivered, 4),
            'invoiced_qty' => round($invoiced, 4),
            'remaining_qty' => round(max($contractQty - $ordered, 0), 4),
            'contract_value' => round($contractQty * $price, 2),
            'realized_value' => round($invoiced * $price, 2),
            'progress_pct' => $contractQty > 0 ? round($delivered / $contractQty * 100, 2) : 0,
        ];
    }

    /**
     * Guard called when creating a sales order line.
     */
    public static function assertSalesWithinContract(int $customerId, int $itemId, float $newQty, ?int $excludeSoId = null, ?string $date = null): void
    {
        $contract = self::activeCustomerContract($customerId, $itemId, $date);
        if (!$contract) {
            return; // spot sales allowed when no active contract
        }
        $ordered = (float) SalesOrder::where('customer_id', $customerId)
            ->whereNotIn('status', ['DRAFT', 'REJECTED', 'CANCELLED'])
            ->when($excludeSoId, fn ($q) => $q->where('sales_orders.id', '!=', $excludeSoId))
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_order_items.item_id', $itemId)
            ->sum('sales_order_items.qty');

        $remaining = (float) $contract->contract_qty - $ordered;
        if ($newQty > $remaining + 0.0001) {
            $canOverride = auth()->user()?->hasPermission('contract.override') ?? false;
            if (!$canOverride) {
                throw new \DomainException(
                    'Melebihi kontrak ' . $contract->number . ' (sisa ' . number_format($remaining, 2) . '). ' .
                    'Butuh izin contract.override / approval khusus.'
                );
            }
        }
    }

    public static function supplierRealization(SupplierContract $c): array
    {
        $received = $c->item_id ? (float) GoodsReceiptItem::where('item_id', $c->item_id)
            ->whereHas('receipt', function ($q) use ($c) {
                $q->where('status', 'POSTED')
                    ->whereHas('purchaseOrder', fn ($w) => $w->where('supplier_id', $c->supplier_id));
            })->sum('qty_accepted') : 0;

        $billed = (float) \App\Models\VendorBill::where('supplier_id', $c->supplier_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->sum('total');

        return [
            'received_qty' => round($received, 4),
            'billed_value' => round($billed, 2),
            'remaining_qty' => $c->contract_qty !== null ? round(max((float) $c->contract_qty - $received, 0), 4) : null,
            'remaining_value' => $c->contract_value !== null ? round(max((float) $c->contract_value - $billed, 0), 2) : null,
        ];
    }
}
