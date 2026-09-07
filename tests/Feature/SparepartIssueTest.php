<?php

namespace Tests\Feature;

use App\Models\MaintenanceCost;
use App\Models\WorkOrder;
use App\Services\StockService;

class SparepartIssueTest extends AdminFlowTestCase
{
    protected function stockUp($item, float $qty = 50): void
    {
        StockService::move($this->warehouse->id, $item->id, 'OPENING', $qty, 0, $this->co->id, $this->site->id, null, 'OPENING_BALANCE', 'TEST-OPEN', 100000, today()->toDateString());
    }

    public function test_generic_issue_posts_out(): void
    {
        $item = $this->makeSparepart('SPR-020');
        $this->stockUp($item);

        $this->post('/sparepart/issue', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 5, 'issue_date' => today()->toDateString(),
            'reason' => 'CONSUMPTION', 'received_by' => 'Mekanik A',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_ledger', [
            'item_id' => $item->id, 'movement_type' => 'SPAREPART_OUT',
        ]);
    }

    public function test_wo_issue_creates_cost_and_journal(): void
    {
        $item = $this->makeSparepart('SPR-021');
        $this->stockUp($item);
        $wo = WorkOrder::create([
            'number' => 'WO-TEST-'.uniqid(), 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'date' => today()->toDateString(),
            'status' => 'APPROVED', 'created_by' => $this->admin->id,
        ]);

        $this->post('/sparepart/issue', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 2, 'issue_date' => today()->toDateString(),
            'reason' => 'WORK_ORDER', 'work_order_id' => $wo->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('maintenance_costs', ['work_order_id' => $wo->id, 'cost_type' => 'PART']);
        $cost = MaintenanceCost::where('work_order_id', $wo->id)->firstOrFail();
        $this->assertNotNull($cost->journal_entry_id);
        $this->assertDatabaseHas('journal_entries', ['id' => $cost->journal_entry_id, 'status' => 'POSTED']);
    }
}
