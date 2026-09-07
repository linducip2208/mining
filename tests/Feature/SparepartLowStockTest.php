<?php

namespace Tests\Feature;

use App\Services\SparepartService;
use App\Services\StockService;

class SparepartLowStockTest extends AdminFlowTestCase
{
    public function test_status_thresholds(): void
    {
        $item = $this->makeSparepart('SPR-060', ['min_stock' => 30, 'reorder_point' => 20]);
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 100, 0, $this->co->id, $this->site->id);

        $this->assertEquals('NORMAL', SparepartService::stockStatus($item, 50));
        $this->assertEquals('LOW', SparepartService::stockStatus($item, 25));
        $this->assertEquals('CRITICAL', SparepartService::stockStatus($item, 20));
        $this->assertEquals('CRITICAL', SparepartService::stockStatus($item, 5));
        $this->assertEquals('OUT_OF_STOCK', SparepartService::stockStatus($item, 0));

        $this->get('/sparepart/reports?type=minstock')->assertOk();
    }
}
