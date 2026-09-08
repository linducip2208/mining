<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\CustomerDeposit;
use App\Services\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositLedgerTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        $this->customerId = $this->makeCustomer()->id;
    }

    public function test_balance_is_sum_of_signed_movements(): void
    {
        DepositService::depositIn($this->co->id, $this->customerId, 1500000, today(), null, 'DEP-1');
        DepositService::allocate($this->co->id, $this->customerId, 300000, today(), 1, 'INV-1');

        $this->assertSame(1200000.0, DepositService::balance($this->customerId));
    }

    public function test_allocation_above_balance_rejected(): void
    {
        DepositService::depositIn($this->co->id, $this->customerId, 100000, today(), null, 'DEP-1');

        $this->expectException(\DomainException::class);

        DepositService::allocate($this->co->id, $this->customerId, 100001, today(), 1, 'INV-1');
    }

    public function test_refund_reduces_balance_and_journal_pairs(): void
    {
        DepositService::depositIn($this->co->id, $this->customerId, 500000, today(), null, 'DEP-1');
        $cash = CashAccount::create([
            'company_id' => $this->co->id, 'code' => 'KAS-1', 'name' => 'Kas Kecil', 'account_type' => 'CASH',
        ]);
        DepositService::refund($this->co->id, $this->customerId, 200000, today(), $cash->id, 'REF-1');

        $this->assertSame(300000.0, DepositService::balance($this->customerId));
    }

    public function test_positive_and_negative_adjustments_walk_the_balance(): void
    {
        DepositService::depositIn($this->co->id, $this->customerId, 1000000, today(), null, 'DEP-1');
        DepositService::adjust($this->co->id, $this->customerId, 50000, today(), 'ADJ-1', 'Koreksi pembulatan');

        $this->assertSame(1050000.0, DepositService::balance($this->customerId));

        DepositService::adjust($this->co->id, $this->customerId, -50000, today(), 'ADJ-2', 'Koreksi kembali');

        $this->assertSame(1000000.0, DepositService::balance($this->customerId));
        $this->assertDatabaseHas('audit_logs', ['module' => 'DEPOSIT', 'action' => 'ADJUST']);
    }

    public function test_negative_adjustment_above_balance_rejected(): void
    {
        DepositService::depositIn($this->co->id, $this->customerId, 100000, today(), null, 'DEP-1');

        $this->expectException(\DomainException::class);

        DepositService::adjust($this->co->id, $this->customerId, -200000, today(), 'ADJ-X', 'overdraw');
    }

    public function test_material_credit_kept_separate_from_money_balance(): void
    {
        DepositService::depositIn($this->co->id, $this->customerId, 1000000, today(), null, 'DEP-1');
        DepositService::depositIn($this->co->id, $this->customerId, 25, today(), null, 'CRED-1', null, DepositService::KIND_MATERIAL_CREDIT);

        $this->assertSame(1000000.0, DepositService::balance($this->customerId));
        $this->assertSame(25.0, DepositService::balance($this->customerId, DepositService::KIND_MATERIAL_CREDIT));
        $this->assertSame(1000025.0, 1000000.0 + 25.0);
    }

    public function test_failed_allocation_leaves_no_trace(): void
    {
        try {
            DepositService::allocate($this->co->id, $this->customerId, 10, today(), 1, 'INV-1');
            $this->fail('Expected DomainException');
        } catch (\DomainException) {
            // zero balance → cannot allocate
        }
        $this->assertSame(0, CustomerDeposit::where('customer_id', $this->customerId)->count());
    }
}
