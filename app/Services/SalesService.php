<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Setting;
use App\Models\TaxCode;
use App\Models\TaxTransaction;
use App\Models\WeighbridgeTicket;
use Illuminate\Support\Facades\DB;

class SalesService
{
    /**
     * Complete DO: post stock out, attach weighbridge, final qty.
     */
    public static function completeDelivery($deliveryOrder, WeighbridgeTicket $ticket): void
    {
        DB::transaction(function () use ($deliveryOrder, $ticket) {
            // idempotency: a completed DO can never post stock twice
            if (in_array($deliveryOrder->status, ['COMPLETED', 'CANCELLED'])) {
                throw new \DomainException('Surat jalan sudah '.strtolower($deliveryOrder->status).' — posting ganda ditolak.');
            }
            // quality gate: active HOLD blocks delivery (unless released / special-approved)
            QualityService::assertDeliveryClear($deliveryOrder);
            $so = $deliveryOrder->salesOrder;
            $cogsLines = [];

            foreach ($deliveryOrder->items as $line) {
                $finalQty = (float) $ticket->net;
                if ($finalQty <= 0) {
                    throw new \DomainException('Netto timbangan harus > 0.');
                }
                // prevent stock minus (StockService validates again)
                StockService::move(
                    $deliveryOrder->warehouse_id,
                    $line->item_id,
                    'SALE',
                    0,
                    $finalQty,
                    $so->company_id,
                    $so->site_id,
                    $deliveryOrder->id,
                    'DO',
                    $deliveryOrder->number,
                    null,
                    $deliveryOrder->delivery_date->toDateString()
                );

                $line->qty_delivered = $finalQty;
                $line->save();

                // cermin ke stockpile yang terhubung gudang+item; bila saldo pile
                // tidak cukup (pile baru di-link), lewati + catat — selisih akan
                // tertangkap pada survei berikutnya, delivery tidak boleh macet
                try {
                    StockpileService::moveForWarehouse(
                        $deliveryOrder->warehouse_id,
                        $line->item_id,
                        'SALES_OUT',
                        0,
                        $finalQty,
                        $deliveryOrder->id,
                        'DO',
                        $deliveryOrder->number,
                        $deliveryOrder->delivery_date->toDateString(),
                        'Pengiriman '.$deliveryOrder->number
                    );
                } catch (\DomainException $e) {
                    AuditService::log('SKIP', 'STOCKPILE', $deliveryOrder->id, $deliveryOrder::class, null, ['reason' => $e->getMessage()]);
                }

                $soItem = $so->items()->where('item_id', $line->item_id)->first();
                if ($soItem) {
                    $soItem->qty_delivered += $finalQty;
                    if ($soItem->qty_delivered > $soItem->qty + 0.0001) {
                        throw new \DomainException('Total pengiriman melebihi quantity SO.');
                    }
                    $soItem->save();
                }

                // HPP: Dr COGS / Cr Inventory at moving-average cost
                $item = Item::find($line->item_id);
                $cogsValue = round($finalQty * (float) ($item?->avg_cost ?? 0), 2);
                if ($cogsValue > 0) {
                    $invMap = match ($item?->type) {
                        'PRODUCT' => 'INVENTORY_FG',
                        'RAW' => 'INVENTORY_RAW',
                        default => 'INVENTORY_GENERAL',
                    };
                    $cogsLines[] = ['code' => AccountingService::map('COGS'), 'debit' => $cogsValue, 'memo' => 'HPP '.$deliveryOrder->number];
                    $cogsLines[] = ['code' => AccountingService::map($invMap), 'credit' => $cogsValue, 'memo' => 'Persediaan keluar '.$deliveryOrder->number];
                }
            }

            if ($cogsLines !== []) {
                AccountingService::post($so->company_id, $deliveryOrder->delivery_date->toDateString(), $cogsLines, 'SALES_COGS', $deliveryOrder->id, $deliveryOrder->number, 'HPP '.$deliveryOrder->number, 'DO');
            }

            StockService::consumeReservations('DO', $deliveryOrder->id);

            $deliveryOrder->status = 'COMPLETED';
            $deliveryOrder->weighbridge_ticket_id = $ticket->id;
            $deliveryOrder->save();

            $ticket->status = 'POSTED';
            $ticket->save();

            $remaining = $so->items()->sum('qty') - $so->items()->sum('qty_delivered');
            $so->status = $remaining <= 0.0001 ? 'COMPLETED' : 'PARTIALLY_DELIVERED';
            $so->save();

            AuditService::log('POST', 'SALES', $deliveryOrder->id, $deliveryOrder::class, null, ['do' => $deliveryOrder->number, 'net' => $ticket->net]);
        });
    }

    /**
     * Create + post customer invoice from SO (optionally with deposit auto-allocation).
     */
    public static function createInvoice($so, $invoiceDate, ?int $cashAccountId = null, bool $useDeposit = false): Invoice
    {
        return DB::transaction(function () use ($so, $invoiceDate, $useDeposit) {
            // idempotency: one sales order produces at most one active invoice
            $existing = Invoice::where('sales_order_id', $so->id)
                ->whereNotIn('status', ['CANCELLED', 'VOID'])
                ->first();
            if ($existing) {
                throw new \DomainException('SO ini sudah memiliki faktur '.$existing->number.' — faktur ganda ditolak.');
            }
            $undelivered = $so->items->sum('qty_delivered') <= 0;
            if ($undelivered) {
                throw new \DomainException('Tidak dapat membuat invoice: belum ada pengiriman.');
            }

            $customer = $so->customer;
            $subtotal = $so->items->sum(fn ($i) => $i->qty_delivered * $i->unit_price);
            $taxCode = Setting::get('tax.default_sales_tax_code', 'PPN11');
            $taxRate = (float) (TaxCode::where('code', $taxCode)->value('rate') ?? 0);
            $taxAmount = round($subtotal * $taxRate / 100, 2);

            $invoice = Invoice::create([
                'number' => NumberingService::generate('INV', $so->company_id),
                'company_id' => $so->company_id,
                'customer_id' => $so->customer_id,
                'sales_order_id' => $so->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $customer->paymentTerm ? $invoiceDate->copy()->addDays($customer->paymentTerm->days) : null,
                'payment_term_id' => $customer->payment_term_id,
                'tax_code' => $taxCode,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
                'status' => 'POSTED',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'created_by' => auth()->id() ?? 1,
            ]);

            foreach ($so->items()->where('qty_delivered', '>', 0)->get() as $i) {
                $invoice->items()->create([
                    'item_id' => $i->item_id,
                    'qty' => $i->qty_delivered,
                    'unit_price' => $i->unit_price,
                    'total_price' => round($i->qty_delivered * $i->unit_price, 2),
                ]);
            }

            // Auto journal: Dr AR, Cr Revenue, Cr Tax Payable
            $journal = AccountingService::post($so->company_id, $invoiceDate->toDateString(), [
                ['code' => AccountingService::map('AR_TRADE'), 'debit' => $invoice->total, 'memo' => 'Invoice '.$invoice->number],
                ['code' => AccountingService::map('SALES_REVENUE'), 'credit' => $subtotal, 'memo' => 'Penjualan '.$so->number],
                ['code' => AccountingService::map('TAX_PPN_OUT'), 'credit' => $taxAmount, 'memo' => 'PPN keluaran '.$invoice->number],
            ], 'SALES_INVOICE', $invoice->id, $invoice->number, 'Invoice penjualan '.$invoice->number, 'INV');

            $invoice->journal_entry_id = $journal->id;
            $invoice->save();

            // tax transaction record
            TaxTransaction::create([
                'company_id' => $so->company_id,
                'tax_code_id' => TaxCode::where('code', $taxCode)->value('id') ?? 1,
                'transaction_type' => 'SALES',
                'transaction_id' => $invoice->id,
                'transaction_number' => $invoice->number,
                'trx_date' => $invoiceDate,
                'period' => $invoiceDate->format('Y-m'),
                'tax_base' => $subtotal,
                'tax_amount' => $taxAmount,
                'kind' => 'PPN',
            ]);

            // price variance tracking (retail vs realization)
            PriceService::recordInvoiceVariance($invoice);

            // auto allocate deposit: Dr Customer Deposit Liability / Cr AR
            if ($useDeposit) {
                $depositBalance = DepositService::balance($so->customer_id);
                if ($depositBalance > 0) {
                    $alloc = min($depositBalance, $invoice->total);
                    DepositService::allocate($so->company_id, $so->customer_id, $alloc, $invoiceDate, $invoice->id, $invoice->number);
                    AccountingService::post($so->company_id, $invoiceDate instanceof \DateTimeInterface ? $invoiceDate->format('Y-m-d') : $invoiceDate, [
                        ['code' => AccountingService::map('CUSTOMER_DEPOSIT'), 'debit' => $alloc, 'memo' => 'Alokasi deposit ke '.$invoice->number],
                        ['code' => AccountingService::map('AR_TRADE'), 'credit' => $alloc, 'memo' => 'Alokasi deposit ke '.$invoice->number],
                    ], 'DEPOSIT_ALLOCATION', $invoice->id, $invoice->number, 'Alokasi deposit '.$invoice->number, 'INV');
                    $invoice->paid_amount += $alloc;
                    self::checkInvoicePaid($invoice);
                }
            }

            AuditService::log('POST', 'SALES', $invoice->id, Invoice::class, null, ['invoice' => $invoice->number, 'total' => $invoice->total]);

            return $invoice;
        });
    }

    /**
     * Customer payment: allocate to invoices FIFO; posts journal.
     */
    public static function receivePayment(int $companyId, int $customerId, float $amount, $date, string $method, ?int $cashAccountId, ?string $referenceNo = null, array $invoiceIds = []): Payment
    {
        return DB::transaction(function () use ($companyId, $customerId, $amount, $date, $method, $cashAccountId, $referenceNo, $invoiceIds) {
            $payment = Payment::create([
                'number' => NumberingService::generate('RCV', $companyId),
                'type' => 'RECEIVE',
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'payment_date' => $date,
                'method' => $method,
                'cash_account_id' => $cashAccountId,
                'reference_no' => $referenceNo,
                'amount' => $amount,
                'status' => 'POSTED',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'created_by' => auth()->id() ?? 1,
            ]);

            $remaining = $amount;
            $invoices = $invoiceIds
                ? Invoice::whereIn('id', $invoiceIds)->where('customer_id', $customerId)->orderBy('invoice_date')->get()
                : Invoice::where('customer_id', $customerId)->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->orderBy('invoice_date')->get();

            foreach ($invoices as $inv) {
                if ($remaining <= 0.001) {
                    break;
                }
                $due = (float) $inv->total - (float) $inv->paid_amount;
                if ($due <= 0.001) {
                    continue;
                }
                $alloc = min($due, $remaining);
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_type' => 'CUSTOMER',
                    'invoice_id' => $inv->id,
                    'amount' => $alloc,
                ]);
                $inv->paid_amount += $alloc;
                self::checkInvoicePaid($inv);
                $remaining -= $alloc;
            }

            if ($remaining > 0.001) {
                throw new \DomainException('Jumlah pembayaran melebihi total outstanding invoice sebesar Rp '.number_format($amount - $remaining, 2));
            }

            $journal = AccountingService::post($companyId, $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date, [
                ['code' => self::cashCoa($cashAccountId), 'debit' => $amount, 'memo' => 'Pembayaran customer'],
                ['code' => AccountingService::map('AR_TRADE'), 'credit' => $amount, 'memo' => 'Pembayaran customer'],
            ], 'PAYMENT', $payment->id, $payment->number, 'Penerimaan pembayaran');

            $payment->journal_entry_id = $journal->id;
            $payment->save();

            return $payment;
        });
    }

    public static function cashCoa(?int $cashAccountId): string
    {
        if ($cashAccountId) {
            $acc = CashAccount::find($cashAccountId);
            if ($acc?->coa_id) {
                return $acc->coa?->code ?? AccountingService::map('CASH_MAIN');
            }
        }

        return AccountingService::map('CASH_MAIN');
    }

    public static function checkInvoicePaid(Invoice $invoice): void
    {
        if ((float) $invoice->paid_amount >= (float) $invoice->total - 0.001) {
            $invoice->status = 'PAID';
        } elseif ((float) $invoice->paid_amount > 0) {
            $invoice->status = 'PARTIALLY_PAID';
        }
        $invoice->save();
    }
}
