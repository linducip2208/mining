<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\StockAdjustment;
use App\Services\StockService;

class StockOpnamePostingTest extends AdminFlowTestCase
{
    public function test_opname_post_creates_variance_journal(): void
    {
        $item = $this->makeSparepart('SPR-051');
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 20, 0, $this->co->id, $this->site->id, null, 'OPENING_BALANCE', 'O1', 10000, today()->toDateString());

        $adj = StockAdjustment::create([
            'number' => 'OPN-'.uniqid(), 'type' => 'OPNAME', 'warehouse_id' => $this->warehouse->id,
            'company_id' => $this->co->id, 'adjustment_date' => today()->toDateString(),
            'status' => 'APPROVED', 'created_by' => $this->admin->id,
        ]);
        $adj->items()->create(['item_id' => $item->id, 'system_qty' => 20, 'counted_qty' => 17, 'diff_qty' => -3]);

        $this->post("/stock-adjustments/{$adj->id}/post")->assertRedirect();

        $this->assertEquals('POSTED', $adj->fresh()->status);
        $journal = JournalEntry::where('source_type', 'STOCK_OPNAME')->where('source_id', $adj->id)->firstOrFail();
        $this->assertEquals('POSTED', $journal->status);
        $lines = $journal->lines()->with('chartOfAccount')->get();
        $this->assertTrue($lines->contains(fn ($l) => $l->debit > 0 && str_contains($l->chartOfAccount->code, '5-4100')));
        $this->assertTrue($lines->contains(fn ($l) => $l->credit > 0 && str_contains($l->chartOfAccount->code, '1-1320')));
    }
}
