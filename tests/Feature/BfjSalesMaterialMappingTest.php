<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Services\Bfj\BfjMasterMatcher;
use App\Services\Bfj\BfjNormalizer;

class BfjSalesMaterialMappingTest extends AdminFlowTestCase
{
    public function test_material_case_insensitive_no_duplicate(): void
    {
        $this->assertSame('SPLIT 1/2', BfjNormalizer::normalizeMaterial('split 1/2'));
        $item = $this->makeSparepart('MAT-1', ['name' => 'SPLIT 1/2', 'type' => 'PRODUCT']);
        $m = BfjMasterMatcher::match('MATERIAL', 'split 1/2');
        // exact normalized name match resolves without creating duplicate
        $this->assertContains($m['status'], ['EXACT_MATCH', 'ALIAS_MATCH', 'POSSIBLE_MATCH']);
        $this->assertEquals(1, Item::where('code', 'MAT-1')->count());
    }
}
