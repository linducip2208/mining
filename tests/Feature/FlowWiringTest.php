<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\JournalEntry;
use App\Models\Overtime;
use App\Models\PayrollRun;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\Weighbridge;
use App\Models\Warehouse;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\SalesService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowWiringTest extends TestCase
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
        $this->co = Company::create(['code' => 'FW', 'name' => 'Flow Wiring Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'FW-S', 'name' => 'FW Site', 'type' => 'MINE']);
        $this->wh = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'FW-WH', 'name' => 'FW WH']);
    }

    public function test_approved_overtime_flows_into_payroll(): void
    {
        $emp = Employee::create(['code' => 'FW-E1', 'name' => 'FW Emp', 'company_id' => $this->co->id, 'basic_salary' => 5190000, 'status' => 'ACTIVE', 'join_date' => '2024-01-01', 'employment_type' => 'PERMANENT']);
        $period = now()->format('Y-m');
        Overtime::create([
            'number' => 'OT-FW-1', 'employee_id' => $emp->id, 'site_id' => $this->site->id,
            'date' => now()->toDateString(), 'hours' => 10, 'reason' => 'FW lembur',
            'status' => 'APPROVED', 'approved_by' => $this->admin->id, 'created_by' => $this->admin->id,
        ]);

        $run = PayrollRun::create(['number' => 'PYR-FW', 'company_id' => $this->co->id, 'period' => $period, 'status' => 'DRAFT', 'created_by' => $this->admin->id]);
        PayrollService::calculate($run);

        $detail = $run->details()->where('employee_id', $emp->id)->firstOrFail();
        $overtime = collect($detail->components['earnings'])->firstWhere('code', 'OVERTIME');
        $this->assertNotNull($overtime, 'Overtime earning must exist from approved records');
        $this->assertEquals(10, $overtime['hours']);
        // basic 5190000 / 173 × 10h × 1.5 (default rate)
        $this->assertEquals(450000, $overtime['amount']);
    }

    public function test_delivery_posts_cogs_journal(): void
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'FW-P'], ['name' => 'FW Prod', 'type' => 'PRODUCT']);
        $unit = Unit::firstOrCreate(['code' => 'FWT'], ['name' => 'FW Ton']);
        $fg = Item::create(['code' => 'FW-FG', 'name' => 'FW FG', 'item_category_id' => $cat->id, 'type' => 'PRODUCT', 'unit_id' => $unit->id]);
        StockService::move($this->wh->id, $fg->id, 'PURCHASE', 100, 0, $this->co->id, $this->site->id, null, 'PURCHASE', 'PO-FW', 200000, today()->toDateString());

        $customer = Customer::create(['company_id' => $this->co->id, 'code' => 'FW-CU', 'name' => 'FW Customer']);
        $so = SalesOrder::create(['number' => 'SO-FW', 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED', 'subtotal' => 10000000, 'total' => 11100000, 'created_by' => $this->admin->id]);
        $so->items()->create(['item_id' => $fg->id, 'qty' => 50, 'qty_delivered' => 0, 'unit_price' => 200000, 'total_price' => 10000000]);
        $do = DeliveryOrder::create(['number' => 'DO-FW', 'sales_order_id' => $so->id, 'warehouse_id' => $this->wh->id, 'delivery_date' => today(), 'total_qty' => 50, 'status' => 'DRAFT', 'created_by' => $this->admin->id]);
        $do->items()->create(['item_id' => $fg->id, 'qty_ordered' => 50, 'qty_delivered' => 0]);
        $wb = Weighbridge::create(['site_id' => $this->site->id, 'code' => 'FW-WB', 'name' => 'FW WB']);
        $ticket = \App\Models\WeighbridgeTicket::create([
            'ticket_no' => 'WB-FW', 'weighbridge_id' => $wb->id, 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'direction' => 'OUT',
            'first_weight' => 60000, 'second_weight' => 10000,
            'gross' => 60000, 'tare' => 10000, 'net' => 50,
            'customer_id' => $customer->id, 'item_id' => $fg->id,
            'status' => 'COMPLETE', 'created_by' => $this->admin->id,
        ]);

        SalesService::completeDelivery($do, $ticket);

        $journal = JournalEntry::where('source_type', 'SALES_COGS')->where('source_id', $do->id)->firstOrFail();
        $this->assertEquals('POSTED', $journal->status);
        $lines = $journal->lines()->with('chartOfAccount')->get();
        // 50 ton × 200.000 avg = 10.000.000
        $this->assertEquals(10000000, (float) $lines->sum('debit'));
        $this->assertEquals(10000000, (float) $lines->sum('credit'));
        $this->assertTrue($lines->contains(fn ($l) => $l->debit > 0 && $l->chartOfAccount->code === '5-1000'));
        $this->assertTrue($lines->contains(fn ($l) => $l->credit > 0 && $l->chartOfAccount->code === '1-1300'));
    }
}
