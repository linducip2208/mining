<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\MaintenanceCost;
use App\Models\MaintenancePart;
use App\Models\WorkOrder;
use App\Services\MaintenanceService;
use App\Services\SparepartService;
use App\Services\StockService;

class SparepartReturnReversalTest extends AdminFlowTestCase
{
    public function test_return_reverses_maintenance_journal_and_reduces_wo_cost(): void
    {
        $item = $this->makeSparepart('SPR-RET-'.uniqid());
        StockService::move($this->warehouse->id, $item->id, 'OPENING', 10, 0, $this->co->id, $this->site->id, null, 'OPENING_BALANCE', 'O-RET', 25000, today()->toDateString());
        $wo = WorkOrder::create(['number' => 'WO-RET-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'date' => today()->toDateString(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);

        $this->post('/sparepart/issue', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 4, 'issue_date' => today()->toDateString(),
            'reason' => 'WORK_ORDER', 'work_order_id' => $wo->id,
        ])->assertRedirect();
        $wo->refresh();
        $this->assertSame(100000.0, (float) $wo->actual_cost);

        // reserve → issue chain mirrors markIssued so returnIssued can resolve the original cost
        $reservation = SparepartService::reserve($this->warehouse->id, $item->id, $wo, 4);
        $part = MaintenancePart::where('work_order_id', $wo->id)->firstOrFail();
        SparepartService::markIssued($reservation, 4, (float) $part->unit_cost);
        $this->post('/sparepart/return', ['reservation_id' => $reservation->id, 'qty' => 1])->assertRedirect();

        $wo->refresh();
        $this->assertSame(75000.0, (float) $wo->actual_cost);
        // GL: maintenance expense credited back by the returned value
        $reversal = JournalEntry::where('source_type', 'MAINTENANCE_PART_RETURN')->where('status', 'POSTED')->first();
        $this->assertNotNull($reversal);
        $this->assertSame(25000.0, (float) $reversal->total_debit);
        // expense side net = issue 100000 - return 25000
        $partCosts = MaintenanceCost::where('work_order_id', $wo->id)->sum('amount');
        $this->assertSame(75000.0, (float) $partCosts);
        // consumed = issued - returned
        $part = MaintenancePart::where('work_order_id', $wo->id)->first();
        $this->assertSame(3.0, (float) $part->consumed_qty);
        $this->assertSame(1.0, (float) $part->returned_qty);
    }

    public function test_issue_against_completed_work_order_rejected(): void
    {
        $item = $this->makeSparepart('SPR-CMP-'.uniqid());
        $wo = WorkOrder::create(['number' => 'WO-CMP-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'date' => today()->toDateString(), 'status' => 'COMPLETED', 'created_by' => $this->admin->id]);
        $part = MaintenancePart::create([
            'work_order_id' => $wo->id, 'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id, 'qty' => 1,
        ]);

        $this->expectException(\DomainException::class);

        MaintenanceService::issuePart($part);
    }
}
