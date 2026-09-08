<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\Bfj\BfjMasterMatcher;

class BfjSalesCustomerAliasTest extends AdminFlowTestCase
{
    public function test_alias_maps_to_same_customer_and_fuzzy_needs_review(): void
    {
        $c = Customer::create(['company_id' => $this->co->id, 'name' => 'PT Sinar Musi Jaya', 'code' => 'SMJ']);
        $m = BfjMasterMatcher::match('CUSTOMER', 'PT SMJ');
        $this->assertContains($m['status'], ['EXACT_MATCH', 'ALIAS_MATCH']);
        $this->assertEquals($c->id, $m['target_id']);

        $fuzzy = BfjMasterMatcher::match('CUSTOMER', 'Sinar Musi Jay');
        $this->assertNotSame('EXACT_MATCH', $fuzzy['status']);
    }
}
