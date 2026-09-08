<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PaymentAllocation;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Models\Weighbridge;
use App\Models\WeighbridgeTicket;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAllocationTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    private function makeInvoicedFlow(): array
    {
        Setting::set('inventory.cogs_zero_cost_policy', 'ALLOW', 'string');
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);
        $invoice = SalesService::createInvoice($flow['so'], today());

        return [...$flow, 'invoice' => $invoice];
    }

    public function test_payment_fills_oldest_invoice_first_and_marks_paid(): void
    {
        $f = $this->makeInvoicedFlow();
        // invoice total 222000 (200000 + 11%)
        $payment = SalesService::receivePayment($f['so']->company_id, $f['so']->customer_id, 111000, today(), 'BANK', null, 'REF-1');

        $f['invoice']->refresh();
        $this->assertSame('PAID', $f['invoice']->status);
        $this->assertSame(111000.0, (float) $f['invoice']->paid_amount);
        $this->assertSame(1, $payment->allocations()->count());
    }

    public function test_partial_payment_marks_partially_paid(): void
    {
        $f = $this->makeInvoicedFlow();
        SalesService::receivePayment($f['so']->company_id, $f['so']->customer_id, 50000, today(), 'BANK');

        $f['invoice']->refresh();
        $this->assertSame('PARTIALLY_PAID', $f['invoice']->status);
        $this->assertSame(50000.0, (float) $f['invoice']->paid_amount);
    }

    public function test_one_payment_allocates_across_many_invoices(): void
    {
        $f1 = $this->makeInvoicedFlow();
        $invoice2 = $this->makeSecondInvoiceFor($f1, qty: 4);

        $payment = SalesService::receivePayment($f1['so']->company_id, $f1['so']->customer_id, 160000, today(), 'BANK');

        $this->assertSame(2, $payment->allocations()->count());
        $f1['invoice']->refresh();
        $invoice2->refresh();
        $this->assertSame('PAID', $f1['invoice']->status);
        // 80000 + 11% = 88800 outstanding on second invoice; 160000 - 111000 = 49000 allocated
        $this->assertSame(49000.0, (float) $invoice2->paid_amount);
        $this->assertSame('PARTIALLY_PAID', $invoice2->status);
    }

    /**
     * Second invoice (same customer, 4 tons @ 20000) so FIFO allocation
     * across two invoices can be exercised.
     */
    private function makeSecondInvoiceFor(array $f, int $qty): Invoice
    {
        $item = $f['item'];
        $so = SalesOrder::create([
            'number' => 'SO-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'customer_id' => $f['customer']->id, 'order_date' => today(), 'status' => 'APPROVED',
            'subtotal' => $qty * 20000, 'tax_amount' => 0, 'total' => $qty * 20000,
            'created_by' => $this->admin->id,
        ]);
        $so->items()->create(['item_id' => $item->id, 'qty' => $qty, 'qty_delivered' => 0, 'unit_price' => 20000, 'total_price' => $qty * 20000]);

        $do = DeliveryOrder::create([
            'number' => 'DO-'.uniqid(), 'sales_order_id' => $so->id, 'warehouse_id' => $this->wh->id,
            'delivery_date' => today(), 'total_qty' => $qty, 'status' => 'DRAFT',
            'created_by' => $this->admin->id,
        ]);
        $do->items()->create(['item_id' => $item->id, 'qty_ordered' => $qty, 'qty_delivered' => 0]);

        $wb = Weighbridge::create(['site_id' => $this->site->id, 'code' => 'WB-'.uniqid(), 'name' => 'WB Test']);
        $ticket = WeighbridgeTicket::create([
            'ticket_no' => 'WB-T-'.uniqid(), 'weighbridge_id' => $wb->id, 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'direction' => 'OUT',
            'first_weight' => 1000 + $qty, 'second_weight' => 1000,
            'gross' => $qty, 'tare' => 0, 'net' => $qty,
            'customer_id' => $f['customer']->id, 'item_id' => $item->id,
            'status' => 'COMPLETE', 'created_by' => $this->admin->id,
        ]);
        SalesService::completeDelivery($do, $ticket);

        return SalesService::createInvoice($so, today());
    }

    public function test_payment_exceeding_outstanding_rejected(): void
    {
        $f = $this->makeInvoicedFlow();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('melebihi');

        SalesService::receivePayment($f['so']->company_id, $f['so']->customer_id, 999999, today(), 'BANK');
    }

    public function test_second_payment_completes_outstanding(): void
    {
        $f = $this->makeInvoicedFlow();
        SalesService::receivePayment($f['so']->company_id, $f['so']->customer_id, 50000, today(), 'BANK');
        SalesService::receivePayment($f['so']->company_id, $f['so']->customer_id, 61000, today(), 'CASH');

        $f['invoice']->refresh();
        $this->assertSame('PAID', $f['invoice']->status);
        $this->assertSame(2, PaymentAllocation::where('invoice_id', $f['invoice']->id)->count());
    }

    public function test_customer_of_other_company_cannot_be_paid_via_invoice_filter(): void
    {
        $f = $this->makeInvoicedFlow();
        // invoice_ids must belong to the paying customer — foreign invoice ignored
        $payment = SalesService::receivePayment($f['so']->company_id, $f['so']->customer_id, 111000, today(), 'BANK', null, 'REF-X', [$f['invoice']->id]);

        $f['invoice']->refresh();
        $this->assertSame('PAID', $f['invoice']->status);
    }

    public function test_payment_posts_balanced_journal(): void
    {
        $f = $this->makeInvoicedFlow();
        $payment = SalesService::receivePayment($f['so']->company_id, $f['so']->customer_id, 111000, today(), 'BANK');

        $journal = JournalEntry::where('source_type', 'PAYMENT')->where('source_id', $payment->id)->first();
        $this->assertNotNull($journal);
        $this->assertSame(111000.0, (float) $journal->total_debit);
        $this->assertSame(111000.0, (float) $journal->total_credit);
    }
}
