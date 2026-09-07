<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Services\SparepartService;
use App\Services\StockService;

class SparepartPurchaseRecommendationTest extends AdminFlowTestCase
{
    public function test_recommended_qty_and_pr_creation(): void
    {
        $item = $this->makeSparepart('SPR-061', ['max_stock' => 100]);
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 30, 0, $this->co->id, $this->site->id);

        $this->assertEquals(70, SparepartService::recommendedQty($item, 30));

        $this->post('/sparepart/recommend', [
            'company_id' => $this->co->id,
            'items' => [['item_id' => $item->id, 'qty' => 70]],
        ])->assertRedirect();

        $pr = PurchaseRequest::orderByDesc('id')->firstOrFail();
        $this->assertEquals('DRAFT', $pr->status);
        $this->assertEquals(70, (float) $pr->items()->first()->qty);
        $this->assertStringContainsString('rekomendasi', strtolower($pr->notes));
    }
}
