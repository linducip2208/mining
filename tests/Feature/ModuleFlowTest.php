<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PurchaseOrder;
use App\Models\QualityHold;
use App\Models\QualityParameter;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\BudgetService;
use App\Services\ComplianceService;
use App\Services\ContractService;
use App\Services\HseService;
use App\Services\PeriodService;
use App\Services\QualityService;
use App\Services\SalesService;
use App\Services\WeighbridgeTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $co;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $this->seed(\Database\Seeders\AccountingSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
        $this->actingAs($this->admin);
        $this->co = Company::create(['code' => 'FT', 'name' => 'Flow Test Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'FT-S', 'name' => 'FT Site', 'type' => 'MINE']);
    }

    protected function makeLimitedUser(array $permCodes): User
    {
        $role = Role::create(['code' => 'TST_' . uniqid(), 'name' => 'Test Limited']);
        $role->permissions()->sync(\App\Models\Permission::whereIn('code', $permCodes)->pluck('id'));
        $user = User::create(['name' => 'Limited', 'username' => 'limited_' . uniqid(), 'email' => uniqid() . '@t.local', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    protected function makeItem(string $code): Item
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'FT-' . $code], ['name' => 'FT ' . $code, 'type' => 'PRODUCT']);
        $unit = Unit::firstOrCreate(['code' => 'FTT'], ['name' => 'FT Ton']);
        return Item::create(['code' => $code, 'name' => 'FT ' . $code, 'item_category_id' => $cat->id, 'type' => 'PRODUCT', 'unit_id' => $unit->id]);
    }

    // ============ QUALITY HOLD BLOCKS DELIVERY ============

    public function test_hold_blocks_delivery_and_special_unblocks(): void
    {
        $item = $this->makeItem('QCOAL');
        $customer = Customer::create(['company_id' => $this->co->id, 'code' => 'FT-C', 'name' => 'FT Customer']);
        $wh = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'FT-W', 'name' => 'FT WH', 'type' => 'STOCKPILE']);
        $so = SalesOrder::create(['number' => 'SO-FT-1', 'company_id' => $this->co->id, 'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);
        $so->items()->create(['item_id' => $item->id, 'qty' => 100, 'unit_price' => 1000, 'total_price' => 100000]);
        $do = DeliveryOrder::create(['number' => 'DO-FT-1', 'company_id' => $this->co->id, 'sales_order_id' => $so->id, 'warehouse_id' => $wh->id, 'delivery_date' => today(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);
        $do->items()->create(['item_id' => $item->id, 'qty' => 50]);
        \App\Services\StockService::move($wh->id, $item->id, 'OPENING', 100, 0, $this->co->id, $this->site->id);
        QualityService::hold(['sales_order_id' => $so->id, 'item_id' => $item->id, 'customer_id' => $customer->id, 'reason' => 'Uji lab gagal']);

        $ticket = \App\Models\WeighbridgeTicket::create(['ticket_no' => 'WB-FT-1', 'weighbridge_id' => \App\Models\Weighbridge::create(['site_id' => $this->site->id, 'code' => 'WB-FT', 'name' => 'WB FT', 'status' => true])->id, 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'vehicle_plate' => 'B 1 X', 'gross' => 60, 'tare' => 10, 'net' => 50, 'status' => 'VALIDATED']);
        try {
            SalesService::completeDelivery($do->fresh(), $ticket);
            $this->fail('Delivery harus diblokir HOLD.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }

        $hold = QualityHold::where('sales_order_id', $so->id)->first();
        QualityService::specialApprove($hold->fresh(), 'Disetujui khusus oleh QA head');
        SalesService::completeDelivery($do->fresh(), $ticket->fresh());
        $this->assertEquals('COMPLETED', $do->fresh()->status);
    }

    public function test_coa_blocked_for_hold_sample(): void
    {
        $param = QualityParameter::create(['code' => 'FT-TM', 'name' => 'FT Moisture', 'unit' => '%', 'status' => true]);
        $sample = QualityService::createSample(['source_type' => 'STOCKPILE', 'source_id' => 1, 'sample_date' => today()->toDateString()]);
        // tanpa spec: hasil tersimpan PENDING-ish; paksa HOLD via hold manual lalu coba CoA
        $hold = QualityService::hold(['reason' => 'uji', 'item_id' => null]);
        $this->assertEquals('HOLD', $hold->status);
        // sampel tanpa hasil PASS tidak boleh terbit CoA
        try {
            QualityService::issueCoa($sample->id);
            $this->fail('CoA harus ditolak untuk sampel non-PASS.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        $this->assertTrue($param->exists);
    }

    // ============ CONTRACT ============

    public function test_over_contract_so_blocked_and_po_blocked(): void
    {
        // user TANPA contract.override (superadmin selalu bypass)
        $this->actingAs($this->makeLimitedUser(['sales_order.view', 'sales_order.create', 'purchase_order.view', 'purchase_order.create']));
        $item = $this->makeItem('CCOAL');
        $customer = Customer::create(['company_id' => $this->co->id, 'code' => 'FT-C2', 'name' => 'FT Customer 2']);
        \App\Models\CustomerContract::create(['number' => 'CTR-FT-1', 'company_id' => $this->co->id, 'customer_id' => $customer->id, 'item_id' => $item->id, 'contract_qty' => 100, 'price' => 1000, 'start_date' => today()->subMonth()->toDateString(), 'end_date' => today()->addMonth()->toDateString(), 'status' => 'ACTIVE']);
        try {
            ContractService::assertSalesWithinContract($customer->id, $item->id, 150);
            $this->fail('SO over-contract harus ditolak.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        ContractService::assertSalesWithinContract($customer->id, $item->id, 60);

        $supplier = Supplier::create(['company_id' => $this->co->id, 'code' => 'FT-SUP', 'name' => 'FT Supplier']);
        \App\Models\SupplierContract::create(['number' => 'CTR-SFT-1', 'company_id' => $this->co->id, 'supplier_id' => $supplier->id, 'item_id' => $item->id, 'contract_qty' => 50, 'price' => 500, 'start_date' => today()->subMonth()->toDateString(), 'end_date' => today()->addMonth()->toDateString(), 'status' => 'ACTIVE']);
        try {
            ContractService::assertPurchaseWithinContract($supplier->id, $item->id, 80, 40000);
            $this->fail('PO over-contract harus ditolak.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        ContractService::assertPurchaseWithinContract($supplier->id, $item->id, 20, 10000);
    }

    public function test_hauling_settlement_math(): void
    {
        $supplier = Supplier::first() ?? Supplier::create(['company_id' => $this->co->id, 'code' => 'FT-S3', 'name' => 'FT Hauler']);
        $route = \App\Models\HaulingRoute::create(['site_id' => $this->site->id, 'code' => 'FT-RT', 'name' => 'FT Route', 'distance_km' => 10, 'status' => true]);
        $c = \App\Models\HaulingContract::create(['number' => 'CTR-HFT-1', 'company_id' => $this->co->id, 'supplier_id' => $supplier->id, 'hauling_route_id' => $route->id, 'rate_type' => 'PER_TON', 'rate' => 50000, 'minimum_volume' => 1000, 'start_date' => today()->subMonth()->toDateString(), 'end_date' => today()->addMonth()->toDateString(), 'status' => 'ACTIVE']);
        $real = ContractService::haulingRealization($c);
        $this->assertEquals(0, $real['trips']);
        $this->assertEquals(1000, $real['shortfall']);
    }

    // ============ BUDGET ============

    public function test_budget_block_mode_vetoes_and_warns(): void
    {
        // user TANPA budget.override (superadmin selalu bypass)
        $this->actingAs($this->makeLimitedUser(['journal.view', 'ledger.view']));
        \App\Models\Setting::set('budget.enforce', 'block', 'string');
        $coa = ChartOfAccount::where('is_postable', true)->where('type', 'EXPENSE')->first();
        $budget = \App\Models\Budget::create(['number' => 'BGT-FT-1', 'company_id' => $this->co->id, 'year' => now()->year, 'type' => 'OPEX', 'status' => 'APPROVED', 'version' => 1]);
        $budget->lines()->create(['chart_of_account_id' => $coa->id, 'amount' => 1000]);
        $month = now()->format('Y-m');
        try {
            BudgetService::assertAvailable($this->co->id, null, $coa->id, $month, 5000);
            $this->fail('Block mode harus melempar.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        // dalam pagu: lolos tanpa pesan
        $this->assertNull(BudgetService::assertAvailable($this->co->id, null, $coa->id, $month, 500));
        // warning mode: pesan, bukan exception
        \App\Models\Setting::set('budget.enforce', 'warning', 'string');
        $this->assertNotNull(BudgetService::assertAvailable($this->co->id, null, $coa->id, $month, 5000));
    }

    public function test_commitment_release_cycle(): void
    {
        $coa = ChartOfAccount::where('is_postable', true)->first();
        $budget = \App\Models\Budget::create(['number' => 'BGT-FT-2', 'company_id' => $this->co->id, 'year' => now()->year, 'type' => 'OPEX', 'status' => 'APPROVED', 'version' => 1]);
        $line = $budget->lines()->create(['chart_of_account_id' => $coa->id, 'amount' => 100000]);
        BudgetService::commit('PURCHASE_REQUEST', 999001, 'PR-FT-1', $budget->id, $line->id, 40000);
        $report = BudgetService::budgetReport($budget->fresh());
        $this->assertEquals(40000, $report['totals']['committed']);
        BudgetService::release('PURCHASE_REQUEST', 999001);
        $report = BudgetService::budgetReport($budget->fresh());
        $this->assertEquals(0, $report['totals']['committed']);
    }

    // ============ FISCAL / JOURNAL ============

    public function test_closed_period_blocks_posting_and_reopen_restores(): void
    {
        $period = now()->format('Y-m');
        PeriodService::closePeriod($this->co->id, $period);
        try {
            AccountingService::post($this->co->id, today()->toDateString(), [
                ['code' => '1-1000', 'debit' => 100], ['code' => '4-1000', 'credit' => 100],
            ], 'TEST');
            $this->fail('Posting ke periode tutup harus ditolak.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        PeriodService::reopenPeriod($this->co->id, $period, 'uji reopen');
        $entry = AccountingService::post($this->co->id, today()->toDateString(), [
            ['code' => '1-1000', 'debit' => 100], ['code' => '4-1000', 'credit' => 100],
        ], 'TEST');
        $this->assertEquals('POSTED', $entry->status);
    }

    public function test_unbalanced_journal_rejected_and_reversal_mirrors(): void
    {
        try {
            AccountingService::post($this->co->id, today()->toDateString(), [
                ['code' => '1-1000', 'debit' => 100], ['code' => '4-1000', 'credit' => 50],
            ], 'TEST');
            $this->fail('Jurnal tidak balance harus ditolak.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        $entry = AccountingService::post($this->co->id, today()->toDateString(), [
            ['code' => '1-1000', 'debit' => 200], ['code' => '4-1000', 'credit' => 200],
        ], 'TEST');
        $rev = AccountingService::reverse($entry, 'uji');
        $this->assertTrue($rev->is_reversal);
        $this->assertEquals($entry->id, $rev->reversal_of_id);
    }

    // ============ HSE ============

    public function test_hse_action_requires_owner_due_and_verify_before_close(): void
    {
        $report = HseService::report([
            'company_id' => $this->co->id, 'site_id' => $this->site->id, 'kind' => 'INCIDENT',
            'occurred_at' => now()->toDateTimeString(), 'severity' => 'HIGH', 'description' => 'Uji HSE',
        ]);
        try {
            HseService::addAction($report->id, ['action' => 'tanpa owner']);
            $this->fail('Action tanpa owner/due harus ditolak.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        $emp = \App\Models\Employee::create(['company_id' => $this->co->id, 'code' => 'FT-E', 'name' => 'FT Emp', 'status' => 'ACTIVE']);
        $action = HseService::addAction($report->id, ['action' => 'Perbaiki', 'responsible_id' => $emp->id, 'due_date' => today()->addWeek()->toDateString()]);
        HseService::closeAction($action->fresh(), 'evidence foto');
        try {
            HseService::closeReport($report->fresh());
            $this->fail('Close tanpa verifikasi harus ditolak.');
        } catch (\DomainException $e) {
            $this->assertTrue(true);
        }
        HseService::verifyAction($action->fresh());
        HseService::closeReport($report->fresh());
        $this->assertEquals('CLOSED', $report->fresh()->status);
    }

    // ============ COMPLIANCE ============

    public function test_compliance_renew_and_reminder(): void
    {
        $reg = ComplianceService::register([
            'type' => 'PERMIT', 'title' => 'Izin Uji',
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);
        ComplianceService::dispatchReminders();
        $this->assertEquals('EXPIRED', $reg->fresh()->status);
        ComplianceService::renew($reg->fresh(), now()->addYear()->toDateString(), 'DOC/NEW/1');
        $this->assertEquals('ACTIVE', $reg->fresh()->status);
    }

    // ============ PERMISSION ============

    public function test_viewer_cannot_update_or_delete_user(): void
    {
        $viewer = User::create(['name' => 'V', 'username' => 'viewer9', 'email' => 'v9@t.local', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        $viewer->roles()->sync([Role::where('code', 'VIEWER')->first()->id]);
        $this->actingAs($viewer)->put('/users/' . $this->admin->id, ['name' => 'X', 'username' => 'superadmin', 'email' => 'a@b.c', 'status' => 'ACTIVE'])->assertStatus(403);
        $this->actingAs($viewer)->delete('/users/' . $this->admin->id)->assertStatus(403);
    }

    public function test_users_show_route_gone(): void
    {
        $status = $this->actingAs($this->admin)->get('/users/' . $this->admin->id)->status();
        $this->assertContains($status, [404, 405]);
    }

    public function test_engine_fuel_issue_submit_and_center_approve(): void
    {
        $this->seed(\Database\Seeders\ApprovalWorkflowSeeder::class);
        $tank = \App\Models\FuelTank::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'FT-TE', 'name' => 'FT Engine Tank', 'capacity_liter' => 20000, 'fuel_type' => 'SOLAR', 'status' => true]);
        $issue = \App\Models\FuelIssue::create(['number' => 'FI-ENG-1', 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'issue_date' => today()->toDateString(), 'fuel_tank_id' => $tank->id, 'vehicle_plate' => 'B 9 ENG', 'liter' => 50, 'status' => 'DRAFT']);

        // fleet manager (approver) sudah ada sebelum submit agar ter-resolve
        $fm = User::create(['name' => 'FM', 'username' => 'fm_eng', 'email' => 'fmeng@t.local', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        $fm->roles()->sync([Role::where('code', 'FLEET_MANAGER')->first()->id]);

        // requester mengajukan
        $requester = $this->makeLimitedUser(['fuel.view', 'fuel.create']);
        $this->actingAs($requester)->post('/fuel-issues/' . $issue->id . '/approve')->assertRedirect();
        $this->assertEquals('SUBMITTED', $issue->fresh()->status);
        $req = \App\Models\ApprovalRequest::where('transaction_type', 'FUEL_ISSUE')->where('transaction_id', $issue->id)->first();
        $this->assertNotNull($req);
        $this->assertEquals('PENDING', $req->status);

        // fleet manager menyetujui di center
        $action = $req->actions()->where('action', 'PENDING')->orderBy('sequence')->first();
        $action->update(['approver_id' => $fm->id]);
        $this->actingAs($fm)->post('/approvals/approve/act', ['approval_action_id' => $action->id])->assertRedirect();
        $this->assertEquals('APPROVED', $issue->fresh()->status);
    }
}
