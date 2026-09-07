<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;

/**
 * E2E DOCUMENT: penawaran → reserved number → approval → invoice
 * → payment → kwitansi (related chain, no double booking).
 */
class DocumentChainFlowTest extends AdminFlowTestCase
{
    public function test_letter_to_receipt_chain(): void
    {
        // Surat penawaran with reserved number + approval
        $letter = $this->makeLetter();
        $this->post("/letters/{$letter->id}/reserve")->assertRedirect();
        $letter->update(['status' => 'REVIEW']);
        $this->post("/letters/{$letter->id}/approve")->assertRedirect();
        $this->assertEquals('APPROVED', $letter->fresh()->status);
        $number = $letter->fresh()->number;
        $this->assertNotNull($number);

        // Invoice (sales source of truth)
        $invoice = Invoice::create([
            'number' => 'INV-CH-'.uniqid(), 'company_id' => $this->co->id,
            'customer_id' => $this->makeCustomer()->id, 'invoice_date' => today()->toDateString(),
            'subtotal' => 10000000, 'tax_amount' => 1100000, 'total' => 11100000,
            'paid_amount' => 0, 'status' => 'POSTED', 'created_by' => $this->admin->id,
        ]);

        // Payment + kwitansi
        $payment = Payment::create([
            'number' => 'PAY-CH-'.uniqid(), 'type' => 'RECEIVE', 'company_id' => $this->co->id,
            'customer_id' => $invoice->customer_id, 'payment_date' => today()->toDateString(),
            'method' => 'TRANSFER', 'amount' => 11100000, 'status' => 'POSTED',
            'created_by' => $this->admin->id,
        ]);
        $this->post('/receipts', [
            'payment_id' => $payment->id, 'receipt_date' => today()->toDateString(),
            'payment_method' => 'TRANSFER', 'invoice_id' => $invoice->id,
        ])->assertRedirect();

        $receipt = Receipt::where('payment_id', $payment->id)->firstOrFail();
        $this->assertEquals($invoice->id, $receipt->invoice_id);
        $this->assertEquals(11100000, (float) $receipt->amount);

        // related docs visible on register pages
        $this->get("/invoice-register/{$invoice->id}")->assertOk();
        $this->get("/receipts/{$receipt->id}")->assertOk()->assertSee($payment->number);
    }
}
