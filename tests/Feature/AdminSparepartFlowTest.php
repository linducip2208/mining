<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\MaintenanceCost;
use App\Models\StockLedger;
use App\Models\WorkOrder;

/**
 * E2E SPAREPART: master → opening → WO → reserve → issue → cost → accounting.
 */
class AdminSparepartFlowTest extends AdminFlowTestCase
{
    public function test_full_sparepart_chain(): void
    {
        // Master
        $item = $this->makeSparepart('SPR-E2E');

        // Opening stock (stok lama)
        $this->post('/sparepart/receipt', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 10, 'unit_cost' => 50000, 'condition' => 'BAIK',
            'receipt_date' => today()->toDateString(), 'is_opening' => true,
            'reference_no' => 'STOK-LAMA-BATCH1',
        ])->assertRedirect();
        $this->assertDatabaseHas('stock_ledger', ['item_id' => $item->id, 'movement_type' => 'OPENING']);

        // Work order
        $wo = WorkOrder::create(['number' => 'WO-E2E-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id, 'date' => today()->toDateString(), 'status' => 'APPROVED', 'created_by' => $this->admin->id]);

        // Reserve
        $this->post('/sparepart/reserve', [
            'work_order_id' => $wo->id, 'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id, 'qty' => 4,
        ])->assertRedirect();

        // Issue
        $this->post('/sparepart/issue', [
            'item_id' => $item->id, 'warehouse_id' => $this->warehouse->id,
            'qty' => 4, 'issue_date' => today()->toDateString(),
            'reason' => 'WORK_ORDER', 'work_order_id' => $wo->id,
        ])->assertRedirect();

        // Cost + accounting
        $cost = MaintenanceCost::where('work_order_id', $wo->id)->firstOrFail();
        $this->assertEquals(200000, (float) $cost->amount);
        $this->assertEquals('POSTED', JournalEntry::findOrFail($cost->journal_entry_id)->status);

        // Ledger balance
        $in = StockLedger::where('item_id', $item->id)->sum('qty_in');
        $out = StockLedger::where('item_id', $item->id)->sum('qty_out');
        $this->assertEquals(6, $in - $out);

        // Card renders from ledger
        $this->get('/sparepart/card?item_id='.$item->id)->assertOk();

        // Item still the same master (no duplicate)
        $this->assertEquals(1, Item::where('code', 'SPR-E2E')->count());
    }
}
