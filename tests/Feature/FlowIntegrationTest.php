<?php

namespace Tests\Feature;

use App\Models\ApprovalWorkflow;
use App\Models\Asset;
use App\Models\Budget;
use App\Models\BudgetCommitment;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\MaintenanceSchedule;
use App\Models\Overtime;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Services\AccountingService;
use App\Services\ApprovalService;
use App\Services\BrandingService;
use App\Services\ProcurementService;
use App\Services\StockService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Company $co;

    protected Site $site;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);
        $this->seed(AccountingSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
        $this->actingAs($this->admin);
        $this->co = Company::create(['code' => 'FIT', 'name' => 'Flow Integration Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'FIT-S', 'name' => 'FIT Site', 'type' => 'MINE']);
        $this->warehouse = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'FIT-WH', 'name' => 'FIT Warehouse']);
    }

    protected function makeItem(string $code): Item
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'FIT-'.$code], ['name' => 'FIT '.$code, 'type' => 'PRODUCT']);
        $unit = Unit::firstOrCreate(['code' => 'FITT'], ['name' => 'FIT Ton']);

        return Item::create(['code' => $code, 'name' => 'FIT '.$code, 'item_category_id' => $cat->id, 'type' => 'PRODUCT', 'unit_id' => $unit->id, 'standard_cost' => 1000]);
    }

    protected function makeSupplier(): Supplier
    {
        return Supplier::create(['company_id' => $this->co->id, 'code' => 'SUP-'.uniqid(), 'name' => 'FIT Supplier']);
    }

    protected function makeCustomer(): Customer
    {
        return Customer::create(['company_id' => $this->co->id, 'code' => 'CUS-'.uniqid(), 'name' => 'FIT Customer']);
    }

    protected function makePO(Item $item, float $qty): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'number' => 'PO-FIT-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'supplier_id' => $this->makeSupplier()->id, 'order_date' => today(), 'subtotal' => $qty * 1000,
            'total' => $qty * 1000, 'status' => 'APPROVED', 'approved_by' => $this->admin->id, 'created_by' => $this->admin->id,
        ]);
        $po->items()->create(['item_id' => $item->id, 'qty' => $qty, 'unit_price' => 1000, 'total_price' => $qty * 1000]);

        return $po;
    }

    protected function makeGRN(PurchaseOrder $po, Item $item, float $accepted): GoodsReceipt
    {
        $gr = GoodsReceipt::create([
            'number' => 'GRN-FIT-'.uniqid(), 'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id, 'receipt_date' => today(),
            'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ]);
        $gr->items()->create(['item_id' => $item->id, 'qty_received' => $accepted, 'qty_accepted' => $accepted, 'unit_cost' => 1000]);

        return $gr;
    }

    // ============ PROCURE-TO-PAY: PO receipt rollup ============

    public function test_grn_post_rolls_po_status_to_partial_then_complete(): void
    {
        $item = $this->makeItem('ROLL'.uniqid());
        $po = $this->makePO($item, 100);

        ProcurementService::postGoodsReceipt($this->makeGRN($po, $item, 40));
        $this->assertEquals('PARTIALLY_RECEIVED', $po->fresh()->status);

        ProcurementService::postGoodsReceipt($this->makeGRN($po, $item, 60));
        $this->assertEquals('COMPLETED', $po->fresh()->status);
    }

    // ============ APPROVAL CENTER: PR commit parity ============

    public function test_pr_center_approval_commits_budget_like_direct_approve(): void
    {
        $coaId = ChartOfAccount::where('code', AccountingService::map('INVENTORY_GENERAL'))->value('id');
        $this->assertNotNull($coaId);
        $budget = Budget::create(['number' => 'B-FIT-'.uniqid(), 'company_id' => $this->co->id, 'year' => (int) today()->format('Y'), 'type' => 'OPEX', 'status' => 'APPROVED', 'created_by' => $this->admin->id]);
        $budget->lines()->create(['chart_of_account_id' => $coaId, 'period' => today()->format('Y-m'), 'amount' => 100000000]);

        $wf = ApprovalWorkflow::create(['code' => 'FIT-PR', 'name' => 'FIT PR', 'module' => 'PROCUREMENT', 'transaction_type' => 'PURCHASE_REQUEST', 'is_active' => true, 'created_by' => $this->admin->id]);
        $wf->steps()->create(['sequence' => 1, 'name' => 'Manager', 'role_id' => Role::where('code', 'SUPERADMIN')->first()?->id ?? Role::first()->id, 'min_amount' => 0, 'mode' => 'SEQUENCE']);

        $item = $this->makeItem('PRC'.uniqid());
        $pr = PurchaseRequest::create(['number' => 'PR-FIT-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'request_date' => today(), 'status' => 'DRAFT', 'created_by' => $this->admin->id]);
        $pr->items()->create(['item_id' => $item->id, 'qty' => 10]);

        $request = ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);
        $this->assertNotNull($request);
        $action = $request->actions()->first();
        $this->assertTrue(ApprovalService::actOnActionId($action->id, $this->admin, 'APPROVE'));

        $this->assertEquals('APPROVED', $pr->fresh()->status);
        $this->assertEquals(1, BudgetCommitment::where('ref_type', 'PURCHASE_REQUEST')->where('ref_id', $pr->id)->where('status', 'COMMITTED')->count());
    }

    // ============ HR: overtime approve (relation + guard + audit) ============

    public function test_overtime_index_and_approve_work_with_approver_relation(): void
    {
        $emp = Employee::create(['code' => 'EMP-'.uniqid(), 'name' => 'FIT Operator', 'company_id' => $this->co->id, 'site_id' => $this->site->id]);
        $ot = Overtime::create(['number' => 'OT-FIT-'.uniqid(), 'employee_id' => $emp->id, 'site_id' => $this->site->id, 'date' => today(), 'hours' => 2, 'reason' => 'FIT lembur', 'status' => 'DRAFT', 'created_by' => $this->admin->id]);

        $this->get('/overtimes')->assertOk()->assertSee('FIT Operator');

        $this->post("/overtimes/{$ot->id}/approve")->assertRedirect();
        $this->assertEquals('APPROVED', $ot->fresh()->status);
        $this->assertEquals($this->admin->id, $ot->fresh()->approved_by);

        $this->post("/overtimes/{$ot->id}/approve")->assertRedirect();
        $this->assertEquals('APPROVED', $ot->fresh()->status);
    }

    // ============ SALES: SO submit + reserve wiring ============

    public function test_so_submit_then_reserve_flows_to_stock_reservation(): void
    {
        $item = $this->makeItem('SOR'.uniqid());
        StockService::move($this->warehouse->id, $item->id, 'PURCHASE', 50, 0, $this->co->id, $this->site->id);
        Setting::set('inventory.default_warehouse_id', (string) $this->warehouse->id, 'integer');

        $so = SalesOrder::create(['number' => 'SO-FIT-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'customer_id' => $this->makeCustomer()->id, 'order_date' => today(), 'status' => 'DRAFT', 'created_by' => $this->admin->id]);
        $so->items()->create(['item_id' => $item->id, 'qty' => 20, 'unit_price' => 5000, 'total_price' => 100000]);

        $this->post("/sales-orders/{$so->id}/submit")->assertRedirect();
        $this->assertContains($so->fresh()->status, ['SUBMITTED', 'APPROVED']);

        if ($so->fresh()->status === 'SUBMITTED') {
            $this->post("/sales-orders/{$so->id}/approve")->assertRedirect();
        }
        $this->assertEquals('APPROVED', $so->fresh()->status);

        $this->post("/sales-orders/{$so->id}/reserve")->assertRedirect();
        $this->assertDatabaseHas('stock_reservations', ['ref_type' => 'DO', 'ref_id' => $so->id, 'item_id' => $item->id, 'status' => 'RESERVED']);
    }

    // ============ SALES: completed SO without invoice is billable ============

    public function test_invoice_create_offers_completed_so_without_invoice(): void
    {
        $item = $this->makeItem('INB'.uniqid());
        $so = SalesOrder::create(['number' => 'SO-FIT-'.uniqid(), 'company_id' => $this->co->id, 'customer_id' => $this->makeCustomer()->id, 'order_date' => today(), 'status' => 'COMPLETED', 'created_by' => $this->admin->id]);
        $so->items()->create(['item_id' => $item->id, 'qty' => 10, 'qty_delivered' => 10, 'unit_price' => 5000, 'total_price' => 50000]);

        $this->get('/invoices/create')->assertOk()->assertSee($so->number);
    }

    // ============ MAINTENANCE: schedule computes due + generates WO once ============

    public function test_schedule_store_computes_due_and_generate_creates_wo_once(): void
    {
        $asset = Asset::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'AST-'.uniqid(), 'name' => 'FIT Asset']);

        $this->post('/maintenance-schedules', [
            'asset_id' => $asset->id, 'type' => 'PREVENTIVE', 'name' => 'FIT Service',
            'interval_type' => 'DAY', 'interval_value' => 30,
            'last_done' => today()->subDays(40)->toDateString(),
        ])->assertRedirect();

        $schedule = MaintenanceSchedule::latest('id')->first();
        $this->assertEquals(today()->subDays(10)->toDateString(), $schedule->next_due->toDateString());

        $this->post('/maintenance-schedules/generate')->assertRedirect();
        $this->assertEquals(1, WorkOrder::where('maintenance_schedule_id', $schedule->id)->count());
        $this->assertEquals('DRAFT', WorkOrder::where('maintenance_schedule_id', $schedule->id)->first()->status);

        $this->post('/maintenance-schedules/generate')->assertRedirect();
        $this->assertEquals(1, WorkOrder::where('maintenance_schedule_id', $schedule->id)->count());
    }

    // ============ SETTINGS: resilient without table ============

    public function test_setting_get_returns_default_when_table_missing(): void
    {
        Schema::dropIfExists('settings');

        $this->assertSame('fallback', Setting::get('branding.app_name', 'fallback'));
        $this->assertNotEmpty(BrandingService::appName());
    }
}
