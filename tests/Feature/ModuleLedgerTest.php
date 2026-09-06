<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\FuelIssue;
use App\Models\FuelReceipt;
use App\Models\FuelTank;
use App\Models\FuelTransfer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Site;
use App\Models\Stockpile;
use App\Models\Tire;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DispatchService;
use App\Services\FuelService;
use App\Services\StockpileService;
use App\Services\TireService;
use App\Services\WeighbridgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $co;
    protected Site $site;
    protected FuelTank $tank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $this->seed(\Database\Seeders\AccountingSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
        $this->actingAs($this->admin);
        $this->co = Company::create(['code' => 'LT', 'name' => 'Ledger Test Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'LT-S', 'name' => 'LT Site', 'type' => 'MINE']);
        $this->tank = FuelTank::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-T', 'name' => 'LT Tank', 'capacity_liter' => 50000, 'fuel_type' => 'SOLAR', 'status' => true]);
    }

    protected function receive(float $liter = 1000, float $price = 15000): FuelReceipt
    {
        $rc = FuelReceipt::create(['number' => 'FR-' . uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'fuel_tank_id' => $this->tank->id, 'receipt_date' => today()->toDateString(), 'liter' => $liter, 'unit_price' => $price, 'total_cost' => $liter * $price, 'status' => 'APPROVED']);
        FuelService::receive($rc->fresh());
        return $rc->fresh();
    }

    // ============ FUEL LEDGER ============

    public function test_avg_cost_uses_in_rows_only(): void
    {
        $this->receive(100, 10);
        $cat = EquipmentCategory::create(['code' => 'LT-C', 'name' => 'LT Cat', 'type' => 'HEAVY', 'standard_fuel_lph' => 1000, 'fuel_warning_pct' => 500, 'status' => true]);
        $eq = Equipment::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-E', 'name' => 'LT Eq', 'type' => 'EXCAVATOR', 'equipment_category_id' => $cat->id, 'status' => 'AVAILABLE']);
        $issue = FuelIssue::create(['number' => 'FI-' . uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'issue_date' => today()->toDateString(), 'fuel_tank_id' => $this->tank->id, 'equipment_id' => $eq->id, 'liter' => 40, 'status' => 'APPROVED']);
        FuelService::issue($issue->fresh());
        // avg harus tetap 10 (OUT 40L tidak ikut pembagi)
        $this->assertEquals(10, FuelService::tankAvgCost($this->tank->id));
    }

    public function test_negative_stock_rejected(): void
    {
        $this->receive(100, 10);
        $issue = FuelIssue::create(['number' => 'FI-' . uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'issue_date' => today()->toDateString(), 'fuel_tank_id' => $this->tank->id, 'vehicle_plate' => 'B 1 X', 'liter' => 500, 'status' => 'APPROVED']);
        $this->expectException(\DomainException::class);
        FuelService::issue($issue->fresh());
    }

    public function test_duplicate_and_cancelled_post_rejected(): void
    {
        $rc = $this->receive(100, 10);
        $this->expectException(\DomainException::class);
        FuelService::receive($rc); // POSTED ulang
    }

    public function test_cancelled_transfer_cannot_post(): void
    {
        $t2 = FuelTank::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-T2', 'name' => 'LT Tank 2', 'capacity_liter' => 50000, 'fuel_type' => 'SOLAR', 'status' => true]);
        $tr = FuelTransfer::create(['number' => 'FT-' . uniqid(), 'from_tank_id' => $this->tank->id, 'to_tank_id' => $t2->id, 'transfer_date' => today()->toDateString(), 'liter' => 10, 'status' => 'CANCELLED']);
        $this->expectException(\DomainException::class);
        FuelService::transfer($tr->fresh());
    }

    public function test_cross_company_transfer_rejected(): void
    {
        $co2 = Company::create(['code' => 'LT2', 'name' => 'LT Co 2', 'status' => true]);
        $t2 = FuelTank::create(['company_id' => $co2->id, 'code' => 'LT-TX', 'name' => 'LT Tank X', 'capacity_liter' => 50000, 'fuel_type' => 'SOLAR', 'status' => true]);
        $tr = FuelTransfer::create(['number' => 'FT-' . uniqid(), 'from_tank_id' => $this->tank->id, 'to_tank_id' => $t2->id, 'transfer_date' => today()->toDateString(), 'liter' => 10, 'status' => 'DRAFT']);
        $this->expectException(\DomainException::class);
        FuelService::transfer($tr->fresh());
    }

    public function test_inverted_hm_rejected(): void
    {
        $this->receive(500, 10);
        $eq = Equipment::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-E2', 'name' => 'LT Eq2', 'type' => 'EXCAVATOR', 'status' => 'AVAILABLE']);
        $issue = FuelIssue::create(['number' => 'FI-' . uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'issue_date' => today()->toDateString(), 'fuel_tank_id' => $this->tank->id, 'equipment_id' => $eq->id, 'hm_before' => 100, 'hm_after' => 90, 'liter' => 50, 'status' => 'APPROVED']);
        $this->expectException(\DomainException::class);
        FuelService::issue($issue->fresh());
    }

    public function test_anonymous_issue_rejected(): void
    {
        $this->receive(500, 10);
        $issue = FuelIssue::create(['number' => 'FI-' . uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'issue_date' => today()->toDateString(), 'fuel_tank_id' => $this->tank->id, 'liter' => 50, 'status' => 'APPROVED']);
        $this->expectException(\DomainException::class);
        FuelService::issue($issue->fresh());
    }

    // ============ TIRE ============

    public function test_double_mount_blocked(): void
    {
        $eq = Equipment::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-DT', 'name' => 'LT DT', 'type' => 'DUMP_TRUCK', 'status' => 'AVAILABLE']);
        $t1 = Tire::create(['company_id' => $this->co->id, 'serial_no' => 'LT-T1', 'status' => 'STOCK']);
        $t2 = Tire::create(['company_id' => $this->co->id, 'serial_no' => 'LT-T2', 'status' => 'STOCK']);
        TireService::install($t1->id, $eq->id, 'FL', today()->toDateString(), 1000);
        $this->expectException(\DomainException::class);
        TireService::install($t2->id, $eq->id, 'FL', today()->toDateString(), 1000);
    }

    // ============ STOCKPILE ============

    protected function makePile(): Stockpile
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'LT-PROD'], ['name' => 'LT Prod', 'type' => 'PRODUCT']);
        $unit = Unit::firstOrCreate(['code' => 'LTT'], ['name' => 'LT Ton']);
        $item = Item::create(['code' => 'LT-COAL', 'name' => 'LT Coal', 'item_category_id' => $cat->id, 'type' => 'PRODUCT', 'unit_id' => $unit->id]);
        $wh = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-W', 'name' => 'LT WH', 'type' => 'STOCKPILE']);
        return Stockpile::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-SP', 'name' => 'LT Pile', 'item_id' => $item->id, 'warehouse_id' => $wh->id, 'capacity_ton' => 50000, 'survey_threshold_pct' => 3, 'status' => true]);
    }

    public function test_stockpile_balance_and_survey_rebase(): void
    {
        $pile = $this->makePile();
        StockpileService::move($pile->id, 'OPENING', 1000, 0);
        $this->assertEquals(1000, StockpileService::balance($pile->id));

        $survey = StockpileService::survey($pile->id, today()->toDateString(), 1030, 'Tester');
        // mutasi masuk antara survei ↔ approve
        StockpileService::move($pile->id, 'PRODUCTION_IN', 100, 0);
        StockpileService::approveSurvey($survey->fresh(), 'OK');
        // rebase: 1030 survei vs 1100 terkini → adjustment -70 → saldo akhir 1030
        $this->assertEquals(1030, StockpileService::balance($pile->id));
    }

    public function test_stockpile_minus_rejected(): void
    {
        $pile = $this->makePile();
        $this->expectException(\DomainException::class);
        StockpileService::move($pile->id, 'SALES_OUT', 0, 10);
    }

    // ============ DISPATCH ============

    public function test_duplicate_truck_shift_date_blocked(): void
    {
        $shift = \App\Models\Shift::create(['company_id' => $this->co->id, 'code' => 'LT-P', 'name' => 'LT Pagi', 'start_time' => '07:00', 'end_time' => '15:00', 'status' => true]);
        $truck = Equipment::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'LT-TR', 'name' => 'LT Truck', 'type' => 'DUMP_TRUCK', 'status' => 'AVAILABLE']);
        $base = ['company_id' => $this->co->id, 'site_id' => $this->site->id, 'trip_date' => today()->toDateString(), 'shift_id' => $shift->id, 'truck_id' => $truck->id];
        DispatchService::assign($base);
        $this->expectException(\DomainException::class);
        DispatchService::assign($base);
    }
}
