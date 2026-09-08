<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\FuelIssue;
use App\Models\FuelReceipt;
use App\Models\FuelTank;
use App\Models\FuelTransfer;
use App\Models\JournalEntry;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\SystemAlert;
use App\Services\FuelService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Company $co;

    private Site $site;

    private FuelTank $tank;

    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);
        $this->seed(AccountingSeeder::class);
        $admin = User::where('username', 'superadmin')->first();
        $this->actingAs($admin);

        $this->co = Company::create(['code' => 'TST', 'name' => 'Test Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'TST-S', 'name' => 'Test Site', 'type' => 'MINE']);
        $this->tank = FuelTank::create([
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'code' => 'TANK-1', 'name' => 'Main Tank', 'capacity_liter' => 50000,
        ]);
        $unit = Unit::firstOrCreate(['code' => 'TSTU'], ['name' => 'Ton']);
        $cat = EquipmentCategory::create(['code' => 'EXC', 'name' => 'Excavator', 'standard_fuel_lph' => 20]);
        $this->equipment = Equipment::create([
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'code' => 'EX-01', 'name' => 'Excavator 01', 'type' => 'EXCAVATOR',
            'unit_id' => $unit->id, 'equipment_category_id' => $cat->id, 'status' => 'AVAILABLE',
        ]);
    }

    private function receive(float $liter, float $price): void
    {
        $receipt = FuelReceipt::create([
            'number' => 'FR-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'fuel_tank_id' => $this->tank->id, 'receipt_date' => today(),
            'liter' => $liter, 'unit_price' => $price, 'total_cost' => $liter * $price,
            'status' => 'APPROVED', 'created_by' => auth()->id(),
        ]);
        FuelService::receive($receipt);
    }

    private function makeIssue(float $liter, float $hmBefore, float $hmAfter): FuelIssue
    {
        return FuelIssue::create([
            'number' => 'FI-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'issue_date' => today(), 'fuel_tank_id' => $this->tank->id,
            'equipment_id' => $this->equipment->id,
            'hm_before' => $hmBefore, 'hm_after' => $hmAfter, 'liter' => $liter,
            'status' => 'APPROVED', 'created_by' => auth()->id(),
        ]);
    }

    public function test_receipt_posting_adds_ledger_and_journal(): void
    {
        $this->receive(1000, 15000);

        $this->assertSame(1000.0, FuelService::tankBalance($this->tank->id));
        $this->assertDatabaseHas('journal_entries', ['source_type' => 'FUEL_RECEIPT', 'status' => 'POSTED']);
    }

    public function test_issue_rejects_more_than_tank_balance(): void
    {
        $this->receive(100, 15000);
        $issue = $this->makeIssue(500, 100, 110);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('tidak cukup');

        FuelService::issue($issue);
    }

    public function test_issue_posts_ledger_out_journal_expense_and_lph(): void
    {
        $this->receive(1000, 15000);
        $issue = $this->makeIssue(100, 100, 105);

        FuelService::issue($issue);

        $issue->refresh();
        $this->assertSame('POSTED', $issue->status);
        $this->assertSame(20.0, (float) $issue->liter_per_hour);
        $this->assertSame(15000.0, (float) $issue->fuel_price);
        $this->assertSame(900.0, FuelService::tankBalance($this->tank->id));
        $journal = JournalEntry::where('source_type', 'FUEL_ISSUE')->first();
        $this->assertNotNull($journal);
        $this->assertSame(1500000.0, (float) $journal->total_debit);
    }

    public function test_double_issue_is_rejected(): void
    {
        $this->receive(1000, 15000);
        $issue = $this->makeIssue(50, 0, 10);
        FuelService::issue($issue);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('sudah diposting');

        FuelService::issue($issue);
    }

    public function test_abnormal_consumption_flags_critical_and_notifies(): void
    {
        \Notification::fake();
        $this->receive(2000, 15000);
        // 400 L over 1 hour vs standard 20 L/h → +1900% → CRITICAL
        $issue = $this->makeIssue(400, 100, 101);
        FuelService::issue($issue);

        $issue->refresh();
        $this->assertSame('CRITICAL', $issue->variance_status);
        \Notification::assertSentTo(
            User::whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN']))->get(),
            SystemAlert::class
        );
    }

    public function test_transfer_between_tanks_moves_ledger(): void
    {
        $this->receive(500, 15000);
        $tankB = FuelTank::create([
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'code' => 'TANK-2', 'name' => 'Buffer Tank', 'capacity_liter' => 10000,
        ]);
        $transfer = FuelTransfer::create([
            'number' => 'FT-'.uniqid(), 'from_tank_id' => $this->tank->id, 'to_tank_id' => $tankB->id,
            'transfer_date' => today(), 'liter' => 200, 'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        FuelService::transfer($transfer);

        $this->assertSame(300.0, FuelService::tankBalance($this->tank->id));
        $this->assertSame(200.0, FuelService::tankBalance($tankB->id));
    }
}
