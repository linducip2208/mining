<?php

namespace Tests\Feature;

use App\Models\JournalLine;
use App\Models\MaintenanceCost;
use App\Models\WorkOrder;
use App\Services\StockService;

class SparepartAccountingTest extends AdminFlowTestCase
{
    public function test_issue_journal_dr_maintenance_cr_sparepart(): void
    {
        $item = $this->makeSparepart('SPR-063');
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 10, 0, $this->co->id, $this->site->id, null, 'OPENING_BALANCE', 'O-ACC', 25000, today()->toDateString());
        $wo = WorkOrder::create(['number' => 'WO-ACC-'.uniqid(), 'company_id' => $this->co->id, 'date' => today()->toDateString(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);

        $this->post('/sparepart/issue', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 2, 'issue_date' => today()->toDateString(),
            'reason' => 'WORK_ORDER', 'work_order_id' => $wo->id,
        ])->assertRedirect();

        $cost = MaintenanceCost::where('work_order_id', $wo->id)->firstOrFail();
        $lines = JournalLine::where('journal_entry_id', $cost->journal_entry_id)->with('chartOfAccount')->get();
        $this->assertEquals(50000, (float) $lines->sum('debit'));
        $this->assertEquals(50000, (float) $lines->sum('credit'));
        $this->assertTrue($lines->contains(fn ($l) => $l->debit > 0 && $l->chartOfAccount->code === '5-3000'));
        $this->assertTrue($lines->contains(fn ($l) => $l->credit > 0 && $l->chartOfAccount->code === '1-1320'));
    }
}
