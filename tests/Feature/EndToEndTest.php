<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Crusher;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\JournalEntry;
use App\Models\OperatorIncentive;
use App\Models\PayrollRun;
use App\Models\ProductionBatch;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\Warehouse;
use App\Models\Weighbridge;
use App\Models\WeighbridgeTicket;
use App\Models\WorkOrder;
use App\Services\AccountingService;
use App\Services\DepositService;
use App\Services\MaintenanceService;
use App\Services\NumberingService;
use App\Services\PayrollService;
use App\Services\ProcurementService;
use App\Services\ProductionService;
use App\Services\SalesService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $co;
    protected Site $site;
    protected Warehouse $wh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $this->seed(\Database\Seeders\AccountingSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
        $this->actingAs($this->admin);

        $this->co = Company::create(['code' => 'E2E', 'name' => 'E2E Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'E2E-S', 'name' => 'E2E Site', 'type' => 'MINE']);
        $this->wh = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'E2E-W', 'name' => 'E2E WH', 'type' => 'STOCKPILE']);
    }

    protected function makeItem(string $code, string $type, float $avg = 0): Item
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'E2E-' . $type], ['name' => 'E2E ' . $type, 'type' => $type]);
        $unit = Unit::firstOrCreate(['code' => 'E2ET'], ['name' => 'E2E Ton']);
        return Item::create([
            'code' => $code, 'name' => 'E2E ' . $code,
            'item_category_id' => $cat->id, 'type' => $type, 'unit_id' => $unit->id,
            'avg_cost' => $avg, 'standard_cost' => $avg,
        ]);
    }

    protected function makeCashAccount(): CashAccount
    {
        $coa = ChartOfAccount::where('code', '1-1000')->first();
        return CashAccount::create([
            'company_id' => $this->co->id, 'code' => 'E2E-CASH', 'name' => 'E2E Kas',
            'type' => 'CASH', 'coa_id' => $coa->id, 'status' => true,
        ]);
    }

    protected function assertJournalsBalanced(): void
    {
        $sum = \DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'POSTED')
            ->selectRaw('COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c')
            ->first();
        $this->assertEquals(0, bccomp((string) $sum->d, (string) $sum->c, 2), 'Journal harus balance');
        $this->assertGreaterThan(0, (float) $sum->d, 'Harus ada jurnal terposting');
    }

    // ============ TEST A — MINE TO CASH ============

    public function test_a_mine_to_cash(): void
    {
        $raw = $this->makeItem('E2E-RAW', 'RAW');
        $fg = $this->makeItem('E2E-FG', 'PRODUCT');
        $crusher = Crusher::create(['site_id' => $this->site->id, 'code' => 'E2E-CR', 'name' => 'E2E Crusher', 'warehouse_id' => $this->wh->id]);
        $customer = Customer::create(['company_id' => $this->co->id, 'code' => 'E2E-CU', 'name' => 'E2E Customer']);
        $wb = Weighbridge::create(['site_id' => $this->site->id, 'code' => 'E2E-WB', 'name' => 'E2E Timbangan']);
        $cash = $this->makeCashAccount();

        // 1. Mining → stock in raw 1000
        StockService::move($this->wh->id, $raw->id, 'PRODUCTION', 1000, 0, $this->co->id, $this->site->id, null, 'MINING_ACTIVITY', 'MA-E2E', 50000, today()->toDateString());
        $this->assertEquals(1000, StockService::balance($this->wh->id, $raw->id));

        // 2. Production batch: input 800 raw → gross 700, loss 50 → net 650
        $batch = ProductionBatch::create([
            'number' => 'PB-E2E', 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'crusher_id' => $crusher->id, 'date' => today(), 'status' => 'APPROVED',
            'input_tonnage' => 800, 'gross_output' => 700, 'total_loss' => 50, 'total_scrap' => 0, 'net_output' => 650,
            'created_by' => $this->admin->id,
        ]);
        $batch->inputs()->create(['item_id' => $raw->id, 'warehouse_id' => $this->wh->id, 'tonnage' => 800]);
        $batch->outputs()->create(['item_id' => $fg->id, 'warehouse_id' => $this->wh->id, 'gross_tonnage' => 700, 'net_tonnage' => 650]);
        $batch->losses()->create(['category' => 'DEBU', 'tonnage' => 50]);
        ProductionService::post($batch);

        $this->assertEquals(200, StockService::balance($this->wh->id, $raw->id));
        $this->assertEquals(650, StockService::balance($this->wh->id, $fg->id));

        // 3. SO 500 ton @200rb → DO → timbang net 495 → complete
        $so = SalesOrder::create([
            'number' => 'SO-E2E', 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED',
            'subtotal' => 100000000, 'tax_amount' => 11000000, 'total' => 111000000,
            'created_by' => $this->admin->id,
        ]);
        $so->items()->create(['item_id' => $fg->id, 'qty' => 500, 'qty_delivered' => 0, 'unit_price' => 200000, 'total_price' => 100000000]);

        $do = DeliveryOrder::create([
            'number' => 'DO-E2E', 'sales_order_id' => $so->id, 'warehouse_id' => $this->wh->id,
            'delivery_date' => today(), 'total_qty' => 500, 'status' => 'DRAFT',
            'created_by' => $this->admin->id,
        ]);
        $do->items()->create(['item_id' => $fg->id, 'qty_ordered' => 500, 'qty_delivered' => 0]);

        $ticket = WeighbridgeTicket::create([
            'ticket_no' => 'WB-E2E', 'weighbridge_id' => $wb->id, 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'direction' => 'OUT',
            'first_weight' => 16495, 'second_weight' => 16000,
            'gross' => 16495, 'tare' => 16000, 'net' => 495,
            'customer_id' => $customer->id, 'item_id' => $fg->id,
            'status' => 'COMPLETE', 'created_by' => $this->admin->id,
        ]);

        SalesService::completeDelivery($do, $ticket);
        $this->assertEquals(155, StockService::balance($this->wh->id, $fg->id));
        $this->assertEquals('COMPLETED', $do->fresh()->status);

        // idempotency: re-complete ditolak
        try {
            SalesService::completeDelivery($do->fresh(), $ticket);
            $this->fail('Re-complete harus ditolak');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('posting ganda', $e->getMessage());
        }
        $this->assertEquals(155, StockService::balance($this->wh->id, $fg->id));

        // 4. Deposit 50jt (dengan jurnal) → invoice pakai deposit → lunasi sisa
        DepositService::depositIn($this->co->id, $customer->id, 50000000, today()->toDateString(), $cash->id, 'DEP-E2E');
        $this->assertEquals(50000000, DepositService::balance($customer->id));

        $invoice = SalesService::createInvoice($so, today(), null, true);
        $this->assertEquals(99000000, (float) $invoice->subtotal);
        $this->assertEquals(109890000, (float) $invoice->total);
        $this->assertEquals(50000000, (float) $invoice->fresh()->paid_amount);
        $this->assertEquals('PARTIALLY_PAID', $invoice->fresh()->status);
        $this->assertEquals(0, DepositService::balance($customer->id));

        // duplicate invoice ditolak
        try {
            SalesService::createInvoice($so->fresh(), today(), null, false);
            $this->fail('Faktur ganda harus ditolak');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('faktur ganda', strtolower($e->getMessage()));
        }

        SalesService::receivePayment($this->co->id, $customer->id, 59890000, today()->toDateString(), 'BANK_TRANSFER', $cash->id, 'PAY-E2E', [$invoice->id]);
        $this->assertEquals('PAID', $invoice->fresh()->status);

        // 5. Verifikasi accounting
        $this->assertEquals(-99000000, AccountingService::balance(AccountingService::map('SALES_REVENUE')));
        $this->assertEquals(0, AccountingService::balance(AccountingService::map('AR_TRADE')));
        $this->assertEquals(0, DepositService::balance($customer->id));
        $this->assertJournalsBalanced();

        // alokasi deposit tercatat di jurnal (Dr deposit liability / Cr AR)
        $allocJournal = JournalEntry::where('source_type', 'DEPOSIT_ALLOCATION')->where('status', 'POSTED')->first();
        $this->assertNotNull($allocJournal);
        $this->assertEquals(50000000, (float) $allocJournal->total_debit);
    }

    // ============ TEST B — PROCURE TO PAY ============

    public function test_b_procure_to_pay(): void
    {
        $spare = $this->makeItem('E2E-SPR', 'SPAREPART');
        $supplier = Supplier::create(['company_id' => $this->co->id, 'code' => 'E2E-SUP', 'name' => 'E2E Supplier']);
        $cash = $this->makeCashAccount();

        $po = PurchaseOrder::create([
            'number' => 'PO-E2E', 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'supplier_id' => $supplier->id, 'order_date' => today(),
            'subtotal' => 1000000, 'tax_amount' => 110000, 'total' => 1110000,
            'status' => 'APPROVED', 'created_by' => $this->admin->id,
        ]);
        $po->items()->create(['item_id' => $spare->id, 'qty' => 10, 'unit_price' => 100000, 'total_price' => 1000000]);

        $grn = GoodsReceipt::create([
            'number' => 'GRN-E2E', 'purchase_order_id' => $po->id, 'warehouse_id' => $this->wh->id,
            'receipt_date' => today(), 'status' => 'DRAFT', 'qc_status' => 'PASSED',
            'created_by' => $this->admin->id,
        ]);
        $grn->items()->create(['item_id' => $spare->id, 'qty_received' => 10, 'qty_accepted' => 10, 'unit_cost' => 100000]);
        ProcurementService::postGoodsReceipt($grn);
        $this->assertEquals(10, StockService::balance($this->wh->id, $spare->id));

        // over-receipt via HTTP ditolak + stok tidak berubah
        $resp = $this->post('/goods-receipts', [
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->wh->id,
            'receipt_date' => today()->toDateString(),
            'qc_status' => 'PASSED',
            'lines' => [['item_id' => $spare->id, 'qty_received' => 5, 'qty_accepted' => 5]],
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('error');
        $this->assertEquals(10, StockService::balance($this->wh->id, $spare->id));

        $bill = VendorBill::create([
            'number' => 'BILL-E2E', 'supplier_id' => $supplier->id, 'purchase_order_id' => $po->id,
            'bill_date' => today(), 'due_date' => today()->addDays(30),
            'subtotal' => 1000000, 'tax_amount' => 110000, 'total' => 1110000,
            'paid_amount' => 0, 'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ]);
        $bill->items()->create(['item_id' => $spare->id, 'qty' => 10, 'unit_price' => 100000, 'total_price' => 1000000]);
        ProcurementService::postVendorBill($bill);

        // jurnal: Dr Inventory 1jt + Dr PPN-In 110rb / Cr AP 1,11jt
        $journal = $bill->fresh()->journalEntry;
        $this->assertNotNull($journal);
        $this->assertEquals(1110000, (float) $journal->total_debit);
        $this->assertEquals(-1110000, AccountingService::balance(AccountingService::map('AP_TRADE')));

        ProcurementService::payVendorBill($bill->fresh(), 1110000, today()->toDateString(), $cash->id, 'PAYB-E2E');
        $this->assertEquals('PAID', $bill->fresh()->status);
        $this->assertEquals(0, AccountingService::balance(AccountingService::map('AP_TRADE')));

        // overpay ditolak
        try {
            ProcurementService::payVendorBill($bill->fresh(), 1000, today()->toDateString(), $cash->id);
            $this->fail('Overpay harus ditolak');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }

        $this->assertJournalsBalanced();
    }

    // ============ TEST C — PAYROLL TO GL ============

    public function test_c_payroll_to_gl(): void
    {
        $emp1 = Employee::create(['code' => 'E2E-001', 'name' => 'E2E Emp 1', 'company_id' => $this->co->id, 'basic_salary' => 10000000, 'status' => 'ACTIVE', 'join_date' => '2024-01-01', 'employment_type' => 'PERMANENT']);
        $emp2 = Employee::create(['code' => 'E2E-002', 'name' => 'E2E Emp 2', 'company_id' => $this->co->id, 'basic_salary' => 8000000, 'status' => 'ACTIVE', 'join_date' => '2024-01-01', 'employment_type' => 'PERMANENT']);

        $period = now()->subMonth()->format('Y-m');
        OperatorIncentive::create([
            'number' => 'INC-E2E', 'employee_id' => $emp1->id, 'basis' => 'TONNAGE',
            'quantity' => 100, 'rate' => 10000, 'amount' => 1000000,
            'period' => $period, 'status' => 'APPROVED', 'created_by' => $this->admin->id,
        ]);

        $run = PayrollRun::create([
            'number' => 'PYR-E2E', 'company_id' => $this->co->id, 'period' => $period,
            'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ]);

        PayrollService::calculate($run);
        $run->refresh();
        $this->assertEquals(2, $run->employee_count);
        $this->assertEquals(19000000, (float) $run->total_gross);
        $this->assertEquals(500000, (float) $run->total_deduction);
        $this->assertEquals(18500000, (float) $run->total_net);

        $run->update(['status' => 'APPROVED', 'approved_by' => $this->admin->id]);
        PayrollService::post($run);

        $journal = JournalEntry::find($run->fresh()->journal_entry_id);
        $this->assertNotNull($journal);
        $this->assertEquals(19000000, (float) $journal->total_debit);

        // double post ditolak
        try {
            PayrollService::post($run->fresh());
            $this->fail('Double post harus ditolak');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }

        PayrollService::pay($run->fresh(), null);
        $this->assertEquals('PAID', $run->fresh()->status);
        $this->assertEquals(0, AccountingService::balance(AccountingService::map('SALARY_PAYABLE')));
        $this->assertJournalsBalanced();
    }

    // ============ TEST D — MAINTENANCE ============

    public function test_d_maintenance_issue(): void
    {
        $spare = $this->makeItem('E2E-BRG', 'SPAREPART', 500000);
        $eq = Equipment::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'E2E-EX', 'name' => 'E2E Excavator', 'type' => 'EXCAVATOR']);

        StockService::move($this->wh->id, $spare->id, 'OPENING', 10, 0, $this->co->id, $this->site->id, null, null, 'OPEN-E2E', 500000, today()->toDateString());

        $wo = WorkOrder::create([
            'number' => 'WO-E2E', 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'equipment_id' => $eq->id, 'type' => 'CORRECTIVE', 'priority' => 'HIGH',
            'date' => today(), 'description' => 'E2E repair', 'status' => 'APPROVED',
            'created_by' => $this->admin->id,
        ]);
        $part = $wo->parts()->create([
            'item_id' => $spare->id, 'warehouse_id' => $this->wh->id, 'qty' => 2,
            'unit_cost' => 500000, 'total_cost' => 1000000, 'issue_status' => 'PENDING',
        ]);

        MaintenanceService::issuePart($part);

        $this->assertEquals(8, StockService::balance($this->wh->id, $spare->id));
        $this->assertEquals('ISSUED', $part->fresh()->issue_status);
        $this->assertEquals(1000000, (float) $wo->costs()->sum('amount'));

        // issue ganda ditolak (idempotency)
        try {
            MaintenanceService::issuePart($part->fresh());
            $this->fail('Issue ganda harus ditolak');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        $this->assertEquals(8, StockService::balance($this->wh->id, $spare->id));

        $this->assertJournalsBalanced();
    }

    // ============ TEST E — CUSTOMER DEPOSIT ============

    public function test_e_customer_deposit_with_journals(): void
    {
        $customer = Customer::create(['company_id' => $this->co->id, 'code' => 'E2E-CUD', 'name' => 'E2E Deposit Customer']);
        $cash = $this->makeCashAccount();

        DepositService::depositIn($this->co->id, $customer->id, 5000000, today()->toDateString(), $cash->id, 'DEP-E2E');
        $this->assertEquals(5000000, DepositService::balance($customer->id));

        $depJournal = JournalEntry::where('source_type', 'CUSTOMER_DEPOSIT')->where('status', 'POSTED')->first();
        $this->assertNotNull($depJournal);
        $this->assertEquals(5000000, (float) $depJournal->total_debit);

        try {
            DepositService::allocate($this->co->id, $customer->id, 6000000, today()->toDateString());
            $this->fail('Overdraft harus ditolak');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }

        DepositService::allocate($this->co->id, $customer->id, 2000000, today()->toDateString());
        DepositService::refund($this->co->id, $customer->id, 1000000, today()->toDateString(), $cash->id, 'REF-E2E');
        $this->assertEquals(2000000, DepositService::balance($customer->id));

        $this->assertJournalsBalanced();
    }
}
