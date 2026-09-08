<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\BudgetService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BudgetControlTest extends TestCase
{
    use RefreshDatabase;

    private Company $co;

    private ChartOfAccount $coa;

    private User $plainUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);
        $this->seed(AccountingSeeder::class);
        $this->actingAs(User::where('username', 'superadmin')->first());

        $this->co = Company::create(['code' => 'TST', 'name' => 'Test Co', 'status' => true]);
        $this->coa = ChartOfAccount::where('code', AccountingService::map('ADMIN_EXPENSE'))->firstOrFail();
        // a user without budget.override for block-mode assertions
        $viewerRole = Role::where('code', 'VIEWER')->first();
        $this->plainUser = User::create([
            'username' => 'plain-'.uniqid(), 'name' => 'Plain User', 'email' => uniqid().'@test.local',
            'password' => Hash::make('Secret!2345'), 'status' => 'ACTIVE',
            'password_changed_at' => now(),
        ]);
        $this->plainUser->roles()->sync([$viewerRole->id]);
    }

    private function makeBudget(float $amount, ?string $period = null): BudgetLine
    {
        $budget = Budget::create([
            'number' => 'BGT-'.uniqid(), 'company_id' => $this->co->id,
            'year' => (int) now()->format('Y'), 'type' => 'OPEX', 'status' => 'APPROVED',
        ]);
        $period = $period ?? now()->format('Y-m');

        return $budget->lines()->create([
            'chart_of_account_id' => $this->coa->id, 'period' => $period, 'amount' => $amount,
        ]);
    }

    public function test_over_spend_blocked_when_enforce_block(): void
    {
        Setting::set('budget.enforce', 'block', 'string');
        $this->makeBudget(1000000);
        $this->actingAs($this->plainUser);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Melebihi budget');

        BudgetService::assertAvailable($this->co->id, null, $this->coa->id, now()->format('Y-m'), 1500000);
    }

    public function test_over_spend_only_warns_when_enforce_warning(): void
    {
        Setting::set('budget.enforce', 'warning', 'string');
        $this->makeBudget(1000000);

        $warn = BudgetService::assertAvailable($this->co->id, null, $this->coa->id, now()->format('Y-m'), 1500000);

        $this->assertStringContainsString('Melebihi budget', (string) $warn);
    }

    public function test_within_budget_returns_no_warning(): void
    {
        Setting::set('budget.enforce', 'block', 'string');
        $this->makeBudget(1000000);

        $warn = BudgetService::assertAvailable($this->co->id, null, $this->coa->id, now()->format('Y-m'), 500000);

        $this->assertNull($warn);
    }

    public function test_budget_override_permission_continues_past_block(): void
    {
        Setting::set('budget.enforce', 'block', 'string');
        $this->makeBudget(1000000);
        // superadmin has budget.override via role permission sync

        $warn = BudgetService::assertAvailable($this->co->id, null, $this->coa->id, now()->format('Y-m'), 1500000);

        $this->assertStringContainsString('budget.override', (string) $warn);
    }

    public function test_commitment_reduces_available_for_next_check(): void
    {
        Setting::set('budget.enforce', 'block', 'string');
        $line = $this->makeBudget(1000000);
        BudgetService::commit('PURCHASE_ORDER', 999, 'PO-TEST', $line->budget_id, $line->id, 700000);

        $report = BudgetService::lineReport($line->fresh());

        $this->assertSame(700000.0, $report['committed']);
        $this->assertSame(300000.0, $report['available']);
        $this->assertSame(70.0, $report['used_pct']);
    }

    public function test_actual_posted_journal_counts_against_budget(): void
    {
        Setting::set('budget.enforce', 'block', 'string');
        $line = $this->makeBudget(1000000);
        AccountingService::post($this->co->id, now()->toDateString(), [
            ['code' => $this->coa->code, 'debit' => 400000, 'memo' => 'beban test'],
            ['code' => AccountingService::map('CASH_MAIN'), 'credit' => 400000, 'memo' => 'kas keluar'],
        ], 'TEST', 1, 'TEST-1');

        $report = BudgetService::lineReport($line->fresh());

        $this->assertSame(400000.0, $report['actual']);
        $this->assertSame(40.0, $report['used_pct']);
    }

    public function test_no_budget_line_means_no_control(): void
    {
        Setting::set('budget.enforce', 'block', 'string');

        $check = BudgetService::check($this->co->id, null, $this->coa->id, now()->format('Y-m'), 999999999);

        $this->assertTrue($check['ok']);
    }
}
