<?php

namespace Tests\Feature;

use App\Models\StockAdjustment;
use App\Services\StockService;

class StockOpnameTest extends AdminFlowTestCase
{
    public function test_opname_counting_then_review(): void
    {
        $item = $this->makeSparepart('SPR-050');
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 20, 0, $this->co->id, $this->site->id);

        $this->post('/sparepart/opname', [
            'warehouse_id' => $this->warehouse->id,
            'adjustment_date' => today()->toDateString(),
            'reason' => 'Opname bulanan',
            'lines' => [['item_id' => $item->id, 'counted_qty' => 18]],
        ])->assertRedirect();

        $adj = StockAdjustment::where('type', 'OPNAME')->firstOrFail();
        $this->assertEquals('COUNTING', $adj->status);
        $this->assertEquals(-2, (float) $adj->items()->first()->diff_qty);

        $this->post("/sparepart/opname/{$adj->id}/review")->assertRedirect();
        $this->assertEquals('REVIEW', $adj->fresh()->status);
    }
}
