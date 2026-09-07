<?php

namespace Tests\Feature;

use App\Models\StockReservation;
use App\Models\WorkOrder;
use App\Services\SparepartService;
use App\Services\StockService;

class SparepartReservationTest extends AdminFlowTestCase
{
    public function test_reserve_reduces_available_and_rejects_over(): void
    {
        $item = $this->makeSparepart('SPR-030');
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 10, 0, $this->co->id, $this->site->id);
        $wo = WorkOrder::create(['number' => 'WO-R-'.uniqid(), 'company_id' => $this->co->id, 'date' => today()->toDateString(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);

        $this->post('/sparepart/reserve', [
            'work_order_id' => $wo->id, 'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id, 'qty' => 6,
        ])->assertRedirect();

        $this->assertEquals(4, SparepartService::available($this->warehouse->id, $item->id));

        $this->post('/sparepart/reserve', [
            'work_order_id' => $wo->id, 'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id, 'qty' => 5,
        ])->assertRedirect();

        $this->assertEquals(6, (float) StockReservation::where('ref_type', 'WORK_ORDER')->where('ref_id', $wo->id)->sum('qty'));
    }
}
