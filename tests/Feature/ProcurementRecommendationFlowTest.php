<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Services\StockService;

/**
 * E2E PROCUREMENT: low stock → recommendation → PR (approval happens
 * in the standard PR flow afterwards; no auto-purchase).
 */
class ProcurementRecommendationFlowTest extends AdminFlowTestCase
{
    public function test_low_stock_to_pr(): void
    {
        $item = $this->makeSparepart('SPR-PR1', ['max_stock' => 50, 'reorder_point' => 20, 'min_stock' => 10]);
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 5, 0, $this->co->id, $this->site->id);

        $this->get('/sparepart/recommend')->assertOk()->assertSee('SPR-PR1');

        $this->post('/sparepart/recommend', [
            'company_id' => $this->co->id,
            'items' => [['item_id' => $item->id, 'qty' => 45]],
        ])->assertRedirect();

        $pr = PurchaseRequest::orderByDesc('id')->firstOrFail();
        $this->assertEquals('DRAFT', $pr->status);
        $this->assertEquals($item->id, $pr->items()->first()->item_id);
    }
}
