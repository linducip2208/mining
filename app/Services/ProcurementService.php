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

class ProcurementService
{
    /**
     * GRN posting: stock in + moving avg cost + AP via vendor bill when requested.
     */
    public static function postGoodsReceipt(GoodsReceipt $gr): void
    {
        DB::transaction(function () use ($gr) {
            if ($gr->status === 'POSTED') {
                throw new \DomainException('GRN sudah diposting.');
            }
            $po = $gr->purchaseOrder;
            if (!in_array($po->status, ['APPROVED', 'SUBMITTED', 'PARTIALLY_RECEIVED'])) {
                throw new \DomainException('PO belum disetujui.');
            }

            foreach ($gr->items as $line) {
                if ($line->qty_accepted <= 0) {
                    continue;
                }
                // landed cost = PO price + share of other_cost
                $unitCost = (float) $line->unit_cost;
                StockService::move(
                    $gr->warehouse_id,
                    $line->item_id,
                    'PURCHASE',
                    $line->qty_accepted,
                    0,
                    $po->company_id,
                    $po->site_id,
                    $gr->id,
                    'GRN',
                    $gr->number,
                    $unitCost,
                    $gr->receipt_date->toDateString()
                );
            }

            $gr->status = 'POSTED';
            $gr->posted_by = auth()->id();
            $gr->posted_at = now();
            $gr->save();

            AuditService::log('POST', 'PROCUREMENT', $gr->id, GoodsReceipt::class, null, ['grn' => $gr->number]);
        });
    }

    /**
     * Vendor bill posting: Dr Inventory (received qty cost), Dr other/admin expense, Cr AP.
     */
    public static function postVendorBill(VendorBill $bill): void
    {
        DB::transaction(function () use ($bill) {
            if ($bill->status === 'POSTED') {
                throw new \DomainException('Tagihan sudah diposting.');
            }

            $lines = [];
            $inventoryAmount = 0.0;
            $adminAmount = 0.0;

            foreach ($bill->items as $line) {
                $amt = round((float) $line->total_price, 2);
                if ($line->item_id) {
                    $inventoryAmount += $amt;
                } else {
                    $adminAmount += $amt;
                }
            }

            if ($inventoryAmount > 0) {
                $lines[] = ['code' => AccountingService::map('INVENTORY_GENERAL'), 'debit' => $inventoryAmount, 'memo' => 'Pembelian inventory ' . $bill->number];
            }
            if ($adminAmount > 0) {
                $lines[] = ['code' => AccountingService::map('ADMIN_EXPENSE'), 'debit' => $adminAmount, 'memo' => 'Beban administrasi ' . $bill->number];
            }
            $tax = (float) $bill->tax_amount;
            if ($tax > 0) {
                $lines[] = ['code' => AccountingService::map('TAX_PPN_IN'), 'debit' => $tax, 'memo' => 'PPN masukan ' . $bill->number];
            }
            $lines[] = ['code' => AccountingService::map('AP_TRADE'), 'credit' => (float) $bill->total, 'memo' => 'Hutang supplier ' . $bill->supplier->name];

            $companyId = $bill->purchaseOrder->company_id ?? 1;
            // block-mode: tagihan yang melebihi sisa budget ditolak di sini
            $month = substr($bill->bill_date->toDateString(), 0, 7);
            $poSite = $bill->purchaseOrder?->site_id;
            if ($inventoryAmount > 0) {
                $coaId = \App\Models\ChartOfAccount::where('code', AccountingService::map('INVENTORY_GENERAL'))->value('id');
                if ($coaId) {
                    BudgetService::assertAvailable($companyId, $poSite, $coaId, $month, $inventoryAmount);
                }
            }
            if ($adminAmount > 0) {
                $coaId = \App\Models\ChartOfAccount::where('code', AccountingService::map('ADMIN_EXPENSE'))->value('id');
                if ($coaId) {
                    BudgetService::assertAvailable($companyId, $poSite, $coaId, $month, $adminAmount);
                }
            }
            $journal = AccountingService::post($companyId, $bill->bill_date->toDateString(), $lines, 'VENDOR_BILL', $bill->id, $bill->number, 'Tagihan supplier ' . $bill->number, 'BILL');

            $bill->status = 'POSTED';
            $bill->journal_entry_id = $journal->id;
            $bill->save();

            // commitment consumed by actual (bill linked to PO)
            if ($bill->purchase_order_id) {
                BudgetService::consume('PURCHASE_ORDER', $bill->purchase_order_id);
            }

            AuditService::log('POST', 'PROCUREMENT', $bill->id, VendorBill::class, null, ['bill' => $bill->number, 'total' => $bill->total]);
        });
    }

    /**
     * Pay vendor bill: Dr AP, Cr Cash/Bank.
     */
    public static function payVendorBill(VendorBill $bill, float $amount, $date, ?int $cashAccountId, ?string $referenceNo = null): void
    {
        DB::transaction(function () use ($bill, $amount, $date, $cashAccountId, $referenceNo) {
            $outstanding = (float) $bill->total - (float) $bill->paid_amount;
            if ($amount > $outstanding + 0.001) {
                throw new \DomainException('Pembayaran melebihi outstanding tagihan.');
            }

            $companyId = $bill->purchaseOrder->company_id ?? $bill->supplier->company_id ?? 1;

            $journal = AccountingService::post($companyId, $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date, [
                ['code' => AccountingService::map('AP_TRADE'), 'debit' => $amount, 'memo' => 'Pembayaran supplier'],
                ['code' => self::cashCoa($cashAccountId), 'credit' => $amount, 'memo' => 'Pembayaran supplier'],
            ], 'PAYMENT', $bill->id, $bill->number, 'Pembayaran supplier');

            $bill->paid_amount += $amount;
            $bill->status = $bill->paid_amount >= $bill->total - 0.001 ? 'PAID' : 'PARTIALLY_PAID';
            $bill->save();
        });
    }

    protected static function cashCoa(?int $cashAccountId): string
    {
        if ($cashAccountId) {
            $acc = \App\Models\CashAccount::find($cashAccountId);
            if ($acc?->coa_id && $acc->coa) {
                return $acc->coa->code;
            }
        }
        return AccountingService::map('CASH_MAIN');
    }
}
