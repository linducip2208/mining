<?php

namespace Tests\Feature;

use App\Models\StockLedger;

class SparepartReceiptTest extends AdminFlowTestCase
{
    public function test_receipt_posts_to_ledger(): void
    {
        $item = $this->makeSparepart('SPR-010');

        $this->post('/sparepart/receipt', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 25, 'unit_cost' => 150000, 'condition' => 'BAIK',
            'receipt_date' => today()->toDateString(), 'reference_no' => 'PO-TEST-1',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_ledger', [
            'warehouse_id' => $this->warehouse->id, 'item_id' => $item->id,
            'movement_type' => 'SPAREPART_IN',
        ]);
        $this->assertEquals(150000, (float) $item->fresh()->avg_cost);
    }

    public function test_rejected_condition_does_not_enter_stock(): void
    {
        $item = $this->makeSparepart('SPR-011');

        $this->post('/sparepart/receipt', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 5, 'condition' => 'REJECTED',
            'receipt_date' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertEquals(0, StockLedger::where('item_id', $item->id)->count());
    }
}
