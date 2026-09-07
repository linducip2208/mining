<?php

namespace Tests\Feature;

use App\Services\StockService;

class StockCardTest extends AdminFlowTestCase
{
    public function test_card_computed_from_ledger_with_running_balance(): void
    {
        $item = $this->makeSparepart('SPR-040');
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 100, 0, $this->co->id, $this->site->id, null, 'OPENING_BALANCE', 'OPEN-1', 10000, today()->toDateString());
        StockService::move($this->warehouse->id, $item->id, 'SPAREPART_OUT', 0, 30, $this->co->id, $this->site->id, null, 'SPAREPART_ISSUE', 'OUT-1', null, today()->toDateString());

        $response = $this->get('/sparepart/card?item_id='.$item->id);
        $response->assertOk()
            ->assertSee('OPEN-1', false)
            ->assertSee('OUT-1', false)
            ->assertSee('70', false);
    }
}
