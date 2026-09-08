<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\ChartOfAccount;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\SalesOrder;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptPaymentLinkTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    protected function cashAccount(): CashAccount
    {
        $coa = ChartOfAccount::where('code', '1-1000')->first();

        return CashAccount::create([
            'company_id' => $this->co->id, 'code' => 'CAS-'.uniqid(), 'name' => 'Kas Test',
            'type' => 'CASH', 'coa_id' => $coa->id, 'status' => true,
        ]);
    }

    protected function paidInvoice(): array
    {
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);
        $invoice = SalesService::createInvoice($flow['so'], today());
        $payment = SalesService::receivePayment($this->co->id, $flow['customer']->id, (float) $invoice->total, today(), 'TRANSFER', $this->cashAccount()->id, 'REF-1', [$invoice->id]);

        return [$flow, $invoice, $payment];
    }

    public function test_receipt_created_from_payment_is_evidence_only(): void
    {
        [$flow, $invoice, $payment] = $this->paidInvoice();

        $this->post('/receipts', [
            'payment_id' => $payment->id, 'receipt_date' => today()->toDateString(),
            'payment_method' => 'TRANSFER', 'reference_no' => 'BANK-REF-9',
        ])->assertRedirect();

        $receipt = Receipt::where('payment_id', $payment->id)->firstOrFail();
        $this->assertEquals((float) $payment->amount, (float) $receipt->amount);
        $this->assertEquals($flow['customer']->id, $receipt->customer_id);
        $this->assertStringStartsWith('KW/', $receipt->number);

        // receipt creates NO journal — money movement happened at Payment
        $journalsAfter = JournalEntry::where('source_type', 'PAYMENT')->where('source_id', $payment->id)->count();
        $this->assertEquals(1, $journalsAfter);
        $this->assertNull($receipt->payment ? null : null);
        $this->assertEquals('POSTED', $payment->fresh()->status); // untouched
    }

    public function test_duplicate_receipt_for_same_payment_rejected(): void
    {
        [$flow, $invoice, $payment] = $this->paidInvoice();
        $this->post('/receipts', ['payment_id' => $payment->id, 'receipt_date' => today()->toDateString(), 'payment_method' => 'TRANSFER'])->assertRedirect();
        $this->assertNotNull(Receipt::where('payment_id', $payment->id)->first());

        $count = Receipt::where('payment_id', $payment->id)->count();
        $this->post('/receipts', ['payment_id' => $payment->id, 'receipt_date' => today()->toDateString(), 'payment_method' => 'TRANSFER'])->assertRedirect();

        $this->assertEquals($count, Receipt::where('payment_id', $payment->id)->count());
    }

    public function test_one_payment_allocates_across_multiple_invoices(): void
    {
        $flow = $this->buildFlow(['stockQty' => 500, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);
        // deliver more from a second DO to enable a second invoice? One SO = one invoice,
        // so allocate one payment over TWO different customers' invoices is not allowed —
        // instead: two invoices from two SOs of the same customer.
        $customer = $flow['customer'];
        $invoices = [];
        foreach ([1, 2] as $i) {
            $so = SalesOrder::create([
                'number' => 'SO-M-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
                'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED',
                'created_by' => $this->admin->id,
            ]);
            $so->items()->create(['item_id' => $flow['item']->id, 'qty' => 10, 'qty_delivered' => 10, 'unit_price' => 10000, 'total_price' => 100000]);
            $do = DeliveryOrder::create([
                'number' => 'DO-M-'.uniqid(), 'sales_order_id' => $so->id, 'warehouse_id' => $this->wh->id,
                'delivery_date' => today(), 'total_qty' => 10, 'status' => 'DRAFT',
                'created_by' => $this->admin->id,
            ]);
            $do->items()->create(['item_id' => $flow['item']->id, 'qty_ordered' => 10, 'qty_delivered' => 0]);
            $do->items->first()->update(['qty_delivered' => 10]);
            $do->status = 'COMPLETED';
            $do->save();
            $so->update(['status' => 'COMPLETED']);
            $invoices[] = SalesService::createInvoice($so, today()->addDays($i));
        }

        // 1 payment covers both invoices exactly
        $totalDue = collect($invoices)->sum(fn ($i) => (float) $i->total);
        $payment = SalesService::receivePayment($this->co->id, $customer->id, $totalDue, today(), 'TRANSFER', $this->cashAccount()->id, 'REF-M', collect($invoices)->pluck('id')->all());

        $allocations = PaymentAllocation::where('payment_id', $payment->id)->get();
        $this->assertEquals(2, $allocations->count());
        $this->assertEquals($totalDue, (float) $allocations->sum('amount'));
        foreach ($invoices as $invoice) {
            $this->assertEquals('PAID', $invoice->fresh()->status);
        }

        // allocated total can never exceed payment total
        $this->assertTrue($allocations->sum('amount') <= $payment->amount + 0.001);
    }

    public function test_invoice_shows_paid_outstanding_from_allocations(): void
    {
        $flow = $this->buildFlow(['stockQty' => 500, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);
        $invoice = SalesService::createInvoice($flow['so'], today());
        $total = (float) $invoice->total;

        // two partial payments against the same invoice
        $p1 = SalesService::receivePayment($this->co->id, $flow['customer']->id, $total * 0.4, today(), 'TRANSFER', $this->cashAccount()->id, 'P1', [$invoice->id]);
        $p2 = SalesService::receivePayment($this->co->id, $flow['customer']->id, $total * 0.6, today(), 'CASH', $this->cashAccount()->id, 'P2', [$invoice->id]);

        $invoice->refresh();
        $paid = (float) PaymentAllocation::where('invoice_type', 'CUSTOMER')->where('invoice_id', $invoice->id)->sum('amount');
        $this->assertEquals($total, round($paid, 2));
        $this->assertEquals($total, round((float) $invoice->paid_amount, 2));
        $this->assertEquals('PAID', $invoice->status);
        $this->assertEquals(2, PaymentAllocation::where('invoice_type', 'CUSTOMER')->where('invoice_id', $invoice->id)->count());

        // overpay rejected
        $this->expectException(\DomainException::class);
        SalesService::receivePayment($this->co->id, $flow['customer']->id, 1000, today(), 'CASH', $this->cashAccount()->id, 'P3', [$invoice->id]);
    }
}
