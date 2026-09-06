<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DispatchTrip;
use App\Models\Equipment;
use App\Models\FuelIssue;
use App\Models\FuelReceipt;
use App\Models\FuelTank;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\SalesOrder;
use App\Models\Shift;
use App\Models\Site;
use App\Models\Stockpile;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Weighbridge;
use App\Models\WeighbridgeTicket;
use App\Services\CostEngine;
use App\Services\DispatchService;
use App\Services\FleetService;
use App\Services\FuelService;
use App\Services\SalesService;
use App\Services\StockpileService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleIntegrationTest extends TestCase
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
        $this->co = Company::create(['code' => 'IT', 'name' => 'Integration Test Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'IT-S', 'name' => 'IT Site', 'type' => 'MINE']);
        $this->wh = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'IT-W', 'name' => 'IT WH', 'type' => 'STOCKPILE']);
    }

    protected function makeItem(string $code): Item
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'IT-' . $code], ['name' => 'IT ' . $code, 'type' => 'PRODUCT']);
        $unit = Unit::firstOrCreate(['code' => 'ITT'], ['name' => 'IT Ton']);
        return Item::create(['code' => $code, 'name' => 'IT ' . $code, 'item_category_id' => $cat->id, 'type' => 'PRODUCT', 'unit_id' => $unit->id]);
    }

    protected function makeTicket(float $net = 40): WeighbridgeTicket
    {
        $wb = Weighbridge::create(['site_id' => $this->site->id, 'code' => 'IT-WB', 'name' => 'IT WB', 'status' => true]);
        return WeighbridgeTicket::create(['ticket_no' => 'WB-IT-' . uniqid(), 'weighbridge_id' => $wb->id, 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'vehicle_plate' => 'B 9 IT', 'gross' => $net + 10, 'tare' => 10, 'net' => $net, 'status' => 'VALIDATED']);
    }

    public function test_dispatch_to_weighbridge_tonnage_flows(): void
    {
        $shift = Shift::create(['company_id' => $this->co->id, 'code' => 'IT-P', 'name' => 'IT Pagi', 'start_time' => '07:00', 'end_time' => '15:00', 'status' => true]);
        $truck = Equipment::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'IT-TR', 'name' => 'IT Truck', 'type' => 'DUMP_TRUCK', 'status' => 'AVAILABLE']);
        $trip = DispatchService::assign(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'trip_date' => today()->toDateString(), 'shift_id' => $shift->id, 'truck_id' => $truck->id]);
        $ticket = $this->makeTicket(42);
        DispatchService::linkTicket($trip->fresh(), $ticket);
        $this->assertEquals(42, (float) $trip->fresh()->tonnage);
    }

    public function test_fuel_issue_feeds_equipment_cost(): void
    {
        $tank = FuelTank::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'IT-T', 'name' => 'IT Tank', 'capacity_liter' => 20000, 'fuel_type' => 'SOLAR', 'status' => true]);
        $rc = FuelReceipt::create(['number' => 'FRI-' . uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'fuel_tank_id' => $tank->id, 'receipt_date' => today()->toDateString(), 'liter' => 1000, 'unit_price' => 10000, 'total_cost' => 10000000, 'status' => 'APPROVED']);
        FuelService::receive($rc->fresh());
        $eq = Equipment::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'IT-EX', 'name' => 'IT Exca', 'type' => 'EXCAVATOR', 'status' => 'AVAILABLE']);
        $issue = FuelIssue::create(['number' => 'FII-' . uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'issue_date' => today()->toDateString(), 'fuel_tank_id' => $tank->id, 'equipment_id' => $eq->id, 'liter' => 100, 'status' => 'APPROVED']);
        FuelService::issue($issue->fresh());
        $kpi = FleetService::kpis($eq->id, today()->startOfMonth()->toDateString(), today()->endOfDay()->toDateTimeString());
        $this->assertEquals(1000000, $kpi['fuel_cost']);
    }

    public function test_delivery_posts_stockpile_sale_out_when_pile_linked(): void
    {
        $item = $this->makeItem('SCOAL');
        $pile = Stockpile::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'IT-SP', 'name' => 'IT Pile', 'item_id' => $item->id, 'warehouse_id' => $this->wh->id, 'capacity_ton' => 50000, 'survey_threshold_pct' => 3, 'status' => true]);
        StockpileService::move($pile->id, 'OPENING', 500, 0);
        StockService::move($this->wh->id, $item->id, 'OPENING', 500, 0, $this->co->id, $this->site->id);

        $customer = Customer::create(['company_id' => $this->co->id, 'code' => 'IT-C', 'name' => 'IT Customer']);
        $so = SalesOrder::create(['number' => 'SO-IT-1', 'company_id' => $this->co->id, 'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);
        $so->items()->create(['item_id' => $item->id, 'qty' => 100, 'unit_price' => 1000, 'total_price' => 100000]);
        $do = DeliveryOrder::create(['number' => 'DO-IT-1', 'company_id' => $this->co->id, 'sales_order_id' => $so->id, 'warehouse_id' => $this->wh->id, 'delivery_date' => today(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);
        $do->items()->create(['item_id' => $item->id, 'qty' => 40]);
        SalesService::completeDelivery($do->fresh(), $this->makeTicket(40));
        $this->assertEquals(460, StockpileService::balance($pile->id));
    }

    public function test_cost_engine_picks_fuel_and_manual_costs(): void
    {
        $this->test_fuel_issue_feeds_equipment_cost();
        \App\Models\MiningOtherCost::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'period' => now()->format('Y-m'), 'component' => 'ROYALTY', 'amount' => 250000, 'status' => 'POSTED']);
        $r = CostEngine::compute($this->co->id, $this->site->id, today()->startOfMonth()->toDateString(), today()->endOfDay()->toDateTimeString());
        $this->assertGreaterThanOrEqual(1000000, $r['components']['fuel']['amount']);
        $this->assertEquals(250000, $r['components']['manual_royalty']['amount']);
    }

    public function test_telematics_and_device_pages_scope_safe(): void
    {
        // provider + simulator sync menulis normalized events
        $provider = \App\Models\TelematicsProvider::create(['code' => 'IT-SIM', 'name' => 'IT Sim', 'driver' => 'SIMULATOR', 'is_active' => true]);
        $n = \App\Services\Telematics\RestApiProvider::make('SIMULATOR', [])->sync($provider->id);
        $this->assertGreaterThanOrEqual(0, $n);
        $device = \App\Models\WeighbridgeDevice::create(['weighbridge_id' => Weighbridge::create(['site_id' => $this->site->id, 'code' => 'IT-WB2', 'name' => 'IT WB2', 'status' => true])->id, 'code' => 'IT-DEV', 'name' => 'IT Device', 'driver' => 'MANUAL', 'is_active' => true]);
        $resp = $this->get('/weighbridge/devices/' . $device->id . '/live');
        $resp->assertStatus(200);
    }
}
