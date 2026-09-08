<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\Tire;
use App\Models\Unit;
use App\Models\User;
use App\Services\TireService;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TireLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private int $companyId;

    private int $equipmentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);
        $this->actingAs(User::where('username', 'superadmin')->first());

        $co = Company::create(['code' => 'TST', 'name' => 'Test Co', 'status' => true]);
        $this->companyId = $co->id;
        $unit = Unit::firstOrCreate(['code' => 'TSTU'], ['name' => 'Ton']);
        $this->equipmentId = Equipment::create([
            'company_id' => $co->id, 'code' => 'DT-01', 'name' => 'Dump Truck 01',
            'type' => 'DUMP_TRUCK', 'unit_id' => $unit->id, 'status' => 'AVAILABLE',
        ])->id;
    }

    private function makeTire(string $serial): Tire
    {
        return Tire::create([
            'company_id' => $this->companyId, 'serial_no' => $serial,
            'brand' => 'TestBrand', 'size' => '27.00R49', 'status' => 'STOCK',
        ]);
    }

    public function test_install_marks_tire_installed_and_records_movement(): void
    {
        $tire = $this->makeTire('SN-'.uniqid());

        TireService::install($tire->id, $this->equipmentId, 'FL', today()->toDateString(), 100);

        $tire->refresh();
        $this->assertSame('INSTALLED', $tire->status);
        $this->assertSame('FL', $tire->position);
        $this->assertDatabaseHas('tire_movements', ['tire_id' => $tire->id, 'movement_type' => 'INSTALL']);
    }

    public function test_installed_tire_cannot_be_installed_again(): void
    {
        $tire = $this->makeTire('SN-'.uniqid());
        TireService::install($tire->id, $this->equipmentId, 'FL', today()->toDateString());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('terpasang');

        TireService::install($tire->id, $this->equipmentId, 'FR', today()->toDateString());
    }

    public function test_same_position_cannot_hold_two_tires(): void
    {
        $a = $this->makeTire('SN-A-'.uniqid());
        $b = $this->makeTire('SN-B-'.uniqid());
        TireService::install($a->id, $this->equipmentId, 'FL', today()->toDateString());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('sudah ditempati');

        TireService::install($b->id, $this->equipmentId, 'FL', today()->toDateString());
    }

    public function test_remove_returns_tire_to_stock_and_clears_position(): void
    {
        $tire = $this->makeTire('SN-'.uniqid());
        TireService::install($tire->id, $this->equipmentId, 'FL', today()->toDateString());

        TireService::remove($tire->id, today()->toDateString(), 'wear', false, 5000);

        $tire->refresh();
        $this->assertSame('STOCK', $tire->status);
        $this->assertNull($tire->equipment_id);
        $this->assertNull($tire->position);
    }

    public function test_scrap_only_from_removed_or_installed_and_records_scrap_movement(): void
    {
        $tire = $this->makeTire('SN-'.uniqid());
        TireService::install($tire->id, $this->equipmentId, 'FL', today()->toDateString());
        TireService::remove($tire->id, today()->toDateString(), 'damage', true, 8000);

        $tire->refresh();
        $this->assertSame('SCRAP', $tire->status);
        $this->assertDatabaseHas('tire_movements', ['tire_id' => $tire->id, 'movement_type' => 'SCRAP']);
    }

    public function test_rotate_changes_position_and_records_movement(): void
    {
        $tire = $this->makeTire('SN-'.uniqid());
        TireService::install($tire->id, $this->equipmentId, 'FL', today()->toDateString());

        TireService::rotate($tire->id, 'RR', today()->toDateString(), 3000);

        $tire->refresh();
        $this->assertSame('RR', $tire->position);
        $this->assertDatabaseHas('tire_movements', ['tire_id' => $tire->id, 'movement_type' => 'ROTATE', 'position' => 'RR']);
    }

    public function test_repair_cost_recorded_on_movement(): void
    {
        $tire = $this->makeTire('SN-'.uniqid());
        TireService::repair($tire->id, today()->toDateString(), 1500000, 'sidewall patch');

        $this->assertDatabaseHas('tire_movements', ['tire_id' => $tire->id, 'movement_type' => 'REPAIR', 'cost' => 1500000]);
    }
}
