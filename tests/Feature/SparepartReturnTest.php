<?php

namespace Tests\Feature;

use App\Models\StockLedger;
use App\Models\StockReservation;
use App\Models\WorkOrder;
use App\Services\StockService;

class SparepartReturnTest extends AdminFlowTestCase
{
    public function test_return_posts_in_with_reference(): void
    {
        $item = $this->makeSparepart('SPR-031');
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 10, 0, $this->co->id, $this->site->id);
        $wo = WorkOrder::create(['number' => 'WO-RT-'.uniqid(), 'company_id' => $this->co->id, 'date' => today()->toDateString(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);
        $res = StockReservation::create([
            'warehouse_id' => $this->warehouse->id, 'item_id' => $item->id,
            'ref_type' => 'WORK_ORDER', 'ref_id' => $wo->id, 'ref_number' => $wo->number,
            'qty' => 4, 'issued_qty' => 4, 'status' => 'RESERVED',
        ]);
        StockService::move($this->warehouse->id, $item->id, 'SPAREPART_OUT', 0, 4, $this->co->id, $this->site->id);

        $this->post('/sparepart/return', ['reservation_id' => $res->id, 'qty' => 2])->assertRedirect();

        $this->assertDatabaseHas('stock_ledger', ['movement_type' => 'RETURN', 'ref_type' => 'SPAREPART_RETURN']);
        $this->assertEquals(2, (float) $res->fresh()->returned_qty);
        $this->assertEquals(8, StockLedger::where('item_id', $item->id)->sum('qty_in') - StockLedger::where('item_id', $item->id)->sum('qty_out'));
    }

    public function test_return_beyond_issued_rejected(): void
    {
        $item = $this->makeSparepart('SPR-032');
        $wo = WorkOrder::create(['number' => 'WO-RT2-'.uniqid(), 'company_id' => $this->co->id, 'date' => today()->toDateString(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);
        $res = StockReservation::create([
            'warehouse_id' => $this->warehouse->id, 'item_id' => $item->id,
            'ref_type' => 'WORK_ORDER', 'ref_id' => $wo->id, 'qty' => 2, 'issued_qty' => 1, 'status' => 'RESERVED',
        ]);

        $this->post('/sparepart/return', ['reservation_id' => $res->id, 'qty' => 5])->assertRedirect();
        $this->assertEquals(0, (float) $res->fresh()->returned_qty);
    }
}
