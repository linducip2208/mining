<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\MaintenancePart;
use App\Models\WorkOrder;
use App\Services\MaintenanceService;
use App\Services\SparepartService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SparepartWorkOrderEndToEndTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    protected function makeSparepart(string $code, float $cost = 50000): Item
    {
        return $this->makeItem($code, 'SPAREPART', $cost);
    }

    protected function makeWo(): WorkOrder
    {
        return WorkOrder::create([
            'number' => 'WO-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'date' => today(), 'description' => 'WO test', 'status' => 'APPROVED',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_reserve_issue_return_consumed_chain_with_original_cost(): void
    {
        $item = $this->makeSparepart('SPR-E2E');
        // receive 10 @ 50000 → avg 50000
        StockService::move($this->wh->id, $item->id, 'SPAREPART_IN', 10, 0, $this->co->id, $this->site->id, null, 'SPAREPART_RECEIPT', 'RC-1', 50000, today()->toDateString());

        $wo = $this->makeWo();
        $res = SparepartService::reserve($this->wh->id, $item->id, $wo, 4);
        $this->assertEquals(4.0, SparepartService::reserved($this->wh->id, $item->id));

        // issue 4 @ current avg 50000
        $part = MaintenancePart::create([
            'work_order_id' => $wo->id, 'item_id' => $item->id, 'warehouse_id' => $this->wh->id,
            'qty' => 4, 'unit_cost' => 50000, 'issue_status' => 'PENDING',
        ]);
        MaintenanceService::issuePart($part->fresh());
        $part->refresh();
        $res->refresh();

        $this->assertEquals('ISSUED', $part->issue_status);
        $this->assertEquals(4.0, (float) $res->issued_qty);
        $this->assertEquals(6.0, StockService::balance($this->wh->id, $item->id));
        $this->assertEquals(0.0, SparepartService::reserved($this->wh->id, $item->id));

        // journal: Dr Maintenance Expense / Cr Inventory Sparepart
        $journal = JournalEntry::where('source_type', 'MAINTENANCE_PART')->firstOrFail();
        $this->assertEquals(200000.0, (float) $journal->total_debit);

        // price moves: receive 6 @ 100000 → moving avg = (6×50000 + 6×100000)/12 = 75000
        StockService::move($this->wh->id, $item->id, 'SPAREPART_IN', 6, 0, $this->co->id, $this->site->id, null, 'SPAREPART_RECEIPT', 'RC-2', 100000, today()->toDateString());
        $item->refresh();
        $this->assertEquals(75000.0, (float) $item->avg_cost);

        // return 2 of the issued — MUST use original issue cost 50000, NOT avg 80000
        $returnLedger = StockService::move($this->wh->id, $item->id, 'RETURN', 2, 0, $this->co->id, null, $res->id, 'SPAREPART_RETURN', $res->ref_number, (float) $res->issued_unit_cost);
        $this->assertEquals(50000.0, (float) $returnLedger->unit_cost);
        $this->assertEquals(100000.0, (float) $returnLedger->total_cost);

        SparepartService::returnIssued($res, 2);
        $res->refresh();
        $this->assertEquals(2.0, (float) $res->returned_qty);
        $this->assertEquals(2.0, (float) $res->consumed_qty); // consumed = issued - returned
    }

    public function test_over_issue_rejected_when_no_stock(): void
    {
        $item = $this->makeSparepart('SPR-NEG');
        $wo = $this->makeWo();

        $this->expectException(\DomainException::class);
        StockService::move($this->wh->id, $item->id, 'MAINTENANCE_USAGE', 0, 5, $this->co->id, $this->site->id, $wo->id, 'WORK_ORDER', $wo->number, 50000);
    }

    public function test_concurrent_reservation_cannot_over_allocate(): void
    {
        $item = $this->makeSparepart('SPR-RACE');
        StockService::move($this->wh->id, $item->id, 'SPAREPART_IN', 10, 0, $this->co->id, $this->site->id, null, 'SPAREPART_RECEIPT', 'RC-3', 50000, today()->toDateString());
        $wo1 = $this->makeWo();
        $wo2 = $this->makeWo();

        SparepartService::reserve($this->wh->id, $item->id, $wo1, 8);

        $this->expectException(\DomainException::class);
        SparepartService::reserve($this->wh->id, $item->id, $wo2, 8);
    }
}
