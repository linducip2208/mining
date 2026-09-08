<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\DepositService;

class BfjDepositConsumptionTest extends AdminFlowTestCase
{
    public function test_balance_comes_from_ledger_not_stored_number(): void
    {
        $cust = Customer::create(['company_id' => $this->co->id, 'name' => 'Customer A', 'code' => 'CA']);
        DepositService::depositIn($this->co->id, $cust->id, 1500000, '2026-08-01', null, 'LEGACY-OPEN');
        DepositService::allocate($this->co->id, $cust->id, 300000, '2026-08-05', null, 'DO-0001');
        $this->assertEquals(1200000, DepositService::balance($cust->id));
    }
}
