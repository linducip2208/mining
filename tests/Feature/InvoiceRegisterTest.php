<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceRegisterController;
use App\Models\Invoice;

class InvoiceRegisterTest extends AdminFlowTestCase
{
    protected function makeInvoice(): Invoice
    {
        return Invoice::create([
            'number' => 'INV-TEST-'.uniqid(), 'company_id' => $this->co->id,
            'customer_id' => $this->makeCustomer()->id, 'invoice_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'subtotal' => 10000000, 'tax_amount' => 1100000, 'total' => 11100000,
            'paid_amount' => 0, 'status' => 'POSTED', 'created_by' => $this->admin->id,
        ]);
    }

    public function test_index_lists_sales_invoices(): void
    {
        $inv = $this->makeInvoice();

        $this->get('/invoice-register')->assertOk()->assertSee($inv->number);
    }

    public function test_display_status_mapping(): void
    {
        $inv = $this->makeInvoice();
        $this->assertEquals('ISSUED', InvoiceRegisterController::displayStatus($inv));

        $inv->update(['status' => 'PAID']);
        $this->assertEquals('LUNAS', InvoiceRegisterController::displayStatus($inv));

        $inv->update(['status' => 'POSTED', 'due_date' => today()->subDay()->toDateString()]);
        $this->assertEquals('OVERDUE', InvoiceRegisterController::displayStatus($inv->fresh()));
    }

    public function test_show_renders_detail_with_terbilang_and_history(): void
    {
        $inv = $this->makeInvoice();

        $this->get("/invoice-register/{$inv->id}")->assertOk()
            ->assertSee('Terbilang')
            ->assertSee('Riwayat Cetak');
    }
}
