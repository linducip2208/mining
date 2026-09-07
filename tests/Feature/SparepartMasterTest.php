<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Unit;

class SparepartMasterTest extends AdminFlowTestCase
{
    public function test_create_sparepart_item(): void
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'FLT'], ['name' => 'Filter', 'type' => 'SPAREPART']);
        $unit = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pcs']);

        $this->post('/sparepart/master', [
            'code' => 'SPR-001', 'name' => 'Filter Oli X',
            'item_category_id' => $cat->id, 'unit_id' => $unit->id,
            'brand' => 'BrandA', 'part_number' => 'PN-123',
            'min_stock' => 5, 'max_stock' => 50, 'reorder_point' => 10,
            'standard_cost' => 150000,
        ])->assertRedirect();

        $item = Item::where('code', 'SPR-001')->firstOrFail();
        $this->assertEquals('SPAREPART', $item->type);
        $this->assertEquals('BrandA', $item->brand);
        $this->assertEquals('NATIVE', $item->source);
    }

    public function test_master_lists_only_spareparts(): void
    {
        $this->makeSparepart('SPR-002');

        $this->get('/sparepart/master')->assertOk()->assertSee('SPR-002');
    }
}
