<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjMasterMatcher;

class BfjEquipmentMappingTest extends AdminFlowTestCase
{
    public function test_unresolved_equipment_queued_not_created(): void
    {
        $m = BfjMasterMatcher::match('EQUIPMENT', 'EXCAVATOR KOBELCO XX');
        $this->assertContains($m['status'], ['POSSIBLE_MATCH', 'NEW_MASTER_REQUIRED']);
        $this->assertNull($m['target_id']);
    }
}
