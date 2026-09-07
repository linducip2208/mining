<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\MaintenanceCost;
use App\Models\StockLedger;
use App\Models\WorkOrder;
use App\Services\SparepartService;
use App\Services\StockService;

class SparepartMaintenanceIntegrationTest extends AdminFlowTestCase
{
    public function test_wo_request_reserve_issue_cost_journal_chain(): void
    {
        $item = $this->makeSparepart('SPR-062');
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 10, 0, $this->co->id, $this->site->id, null, 'OPENING_BALANCE', 'O-CHAIN', 20000, today()->toDateString());
        $wo = WorkOrder::create(['number' => 'WO-CH-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'date' => today()->toDateString(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);

        // REQUEST → RESERVE
        $res = SparepartService::reserve($this->warehouse->id, $item->id, $wo, 4);
        $this->assertEquals(6, SparepartService::available($this->warehouse->id, $item->id));

        // ISSUE via WO (posts cost + journal)
        $this->post('/sparepart/issue', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 4, 'issue_date' => today()->toDateString(),
            'reason' => 'WORK_ORDER', 'work_order_id' => $wo->id,
        ])->assertRedirect();

        $cost = MaintenanceCost::where('work_order_id', $wo->id)->where('cost_type', 'PART')->firstOrFail();
        $this->assertEquals(80000, (float) $cost->amount);
        $this->assertNotNull($cost->journal_entry_id);
        $journal = JournalEntry::findOrFail($cost->journal_entry_id);
        $this->assertEquals('POSTED', $journal->status);
        $this->assertEquals(
            6,
            StockLedger::where('item_id', $item->id)->sum('qty_in') - StockLedger::where('item_id', $item->id)->sum('qty_out')
        );
    }
}
