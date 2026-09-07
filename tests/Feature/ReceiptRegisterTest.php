<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Receipt;

class ReceiptRegisterTest extends AdminFlowTestCase
{
    protected function makePayment(string $status = 'POSTED'): Payment
    {
        return Payment::create([
            'number' => 'PAY-'.uniqid(), 'type' => 'RECEIVE', 'company_id' => $this->co->id,
            'customer_id' => $this->makeCustomer()->id, 'payment_date' => today()->toDateString(),
            'method' => 'TRANSFER', 'amount' => 5000000, 'status' => $status,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_create_from_posted_payment_only(): void
    {
        $payment = $this->makePayment();

        $this->post('/receipts', [
            'payment_id' => $payment->id, 'receipt_date' => today()->toDateString(),
            'payment_method' => 'TRANSFER', 'description' => 'Pelunasan termin 1',
        ])->assertRedirect();

        $receipt = Receipt::where('payment_id', $payment->id)->firstOrFail();
        $this->assertEquals(5000000, (float) $receipt->amount);
        $this->assertEquals('DRAFT', $receipt->status);
        $this->assertStringStartsWith('KW/', $receipt->number);
    }

    public function test_reject_draft_payment(): void
    {
        $payment = $this->makePayment('DRAFT');

        $this->post('/receipts', [
            'payment_id' => $payment->id, 'receipt_date' => today()->toDateString(),
            'payment_method' => 'TRANSFER',
        ])->assertRedirect();

        $this->assertNull(Receipt::where('payment_id', $payment->id)->first());
    }

    public function test_issue_confirm_void_flow(): void
    {
        $payment = $this->makePayment();
        $this->post('/receipts', [
            'payment_id' => $payment->id, 'receipt_date' => today()->toDateString(),
            'payment_method' => 'CASH',
        ]);
        $receipt = Receipt::where('payment_id', $payment->id)->firstOrFail();

        $this->post("/receipts/{$receipt->id}/issue")->assertRedirect();
        $this->assertEquals('ISSUED', $receipt->fresh()->status);

        $this->post("/receipts/{$receipt->id}/confirm")->assertRedirect();
        $this->assertEquals('CONFIRMED', $receipt->fresh()->status);

        $this->post("/receipts/{$receipt->id}/void")->assertRedirect();
        $this->assertEquals('VOID', $receipt->fresh()->status);
        // payment itself untouched — no double money
        $this->assertEquals('POSTED', $payment->fresh()->status);
    }
}
