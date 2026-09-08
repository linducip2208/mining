<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Services\ProcurementService;
use Illuminate\Database\QueryException;

class ProcurementGuardTest extends AdminFlowTestCase
{
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = Supplier::create(['company_id' => $this->co->id, 'code' => 'S-'.uniqid(), 'name' => 'PT Guard']);
    }

    private function makePO(float $subtotal): PurchaseOrder
    {
        $item = $this->makeSparepart('SPR-G-'.uniqid());
        $po = PurchaseOrder::create([
            'number' => 'PO-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'supplier_id' => $this->supplier->id, 'order_date' => today(),
            'subtotal' => $subtotal, 'tax_amount' => round($subtotal * 0.11, 2), 'total' => round($subtotal * 1.11, 2),
            'status' => 'APPROVED', 'created_by' => $this->admin->id,
        ]);
        $po->items()->create(['item_id' => $item->id, 'qty' => 10, 'unit_price' => $subtotal / 10, 'total_price' => $subtotal]);

        return $po;
    }

    private function makeBill(PurchaseOrder $po, float $subtotal): VendorBill
    {
        $bill = VendorBill::create([
            'number' => 'BILL-'.uniqid(), 'supplier_id' => $po->supplier_id, 'purchase_order_id' => $po->id,
            'bill_date' => today(), 'due_date' => today()->addDays(30),
            'subtotal' => $subtotal, 'tax_amount' => round($subtotal * 0.11, 2), 'total' => round($subtotal * 1.11, 2),
            'paid_amount' => 0, 'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ]);
        $bill->items()->create(['item_id' => $po->items()->first()->item_id, 'qty' => 10, 'unit_price' => $subtotal / 10, 'total_price' => $subtotal]);

        return $bill;
    }

    public function test_cumulative_over_bill_rejected_at_post(): void
    {
        $po = $this->makePO(1000000);
        ProcurementService::postVendorBill($this->makeBill($po, 600000));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('melebihi subtotal PO');

        ProcurementService::postVendorBill($this->makeBill($po, 500000));
    }

    public function test_payment_on_draft_bill_rejected(): void
    {
        $po = $this->makePO(1000000);
        $bill = $this->makeBill($po, 100000);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Hanya tagihan POSTED');

        ProcurementService::payVendorBill($bill, 100000, today()->toDateString(), null);
    }

    public function test_payment_on_cancelled_bill_rejected(): void
    {
        $po = $this->makePO(1000000);
        $bill = $this->makeBill($po, 100000);
        $bill->update(['status' => 'CANCELLED']);

        $this->expectException(\DomainException::class);

        ProcurementService::payVendorBill($bill, 100000, today()->toDateString(), null);
    }

    public function test_duplicate_supplier_invoice_no_rejected(): void
    {
        $po = $this->makePO(1000000);
        $bill1 = $this->makeBill($po, 100000);
        $bill1->update(['supplier_invoice_no' => 'SUP-77']);
        $bill2 = $this->makeBill($po, 100000);
        $bill2->supplier_invoice_no = 'SUP-77';

        $this->expectException(QueryException::class);

        $bill2->save();
    }
}
