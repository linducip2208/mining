<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeliveryOrder;
use App\Models\DispatchTrip;
use App\Models\Equipment;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\DispatchService;
use App\Services\SalesService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockIntegrityTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        Setting::set('inventory.cogs_zero_cost_policy', 'ALLOW', 'string');
    }

    public function test_completed_ticket_cannot_complete_second_do(): void
    {
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);

        $do2 = DeliveryOrder::create([
            'number' => 'DO-'.uniqid(), 'sales_order_id' => $flow['so']->id, 'warehouse_id' => $this->wh->id,
            'delivery_date' => today(), 'total_qty' => 10, 'status' => 'DRAFT',
            'created_by' => $this->admin->id,
        ]);
        $do2->items()->create(['item_id' => $flow['item']->id, 'qty_ordered' => 10, 'qty_delivered' => 0]);

        $this->expectException(\DomainException::class);

        SalesService::completeDelivery($do2, $flow['ticket']);
    }

    public function test_reserved_stock_protected_from_unrelated_movement(): void
    {
        $item = $this->makeItem('SP-'.uniqid(), 'SPAREPART');
        StockService::move($this->wh->id, $item->id, 'PURCHASE', 100, 0, $this->co->id, $this->site->id, null, 'OPENING', 'OP-1', 1000, today()->toDateString());
        StockService::reserve($this->wh->id, $item->id, 'WORK_ORDER', 77, 'WO-77', 80);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Terpesan');

        StockService::move($this->wh->id, $item->id, 'ADJUST_OUT', 0, 50, $this->co->id, $this->site->id, 999, 'ADJUST', 'ADJ-1');
    }

    public function test_reserved_stock_usable_by_owning_document(): void
    {
        $item = $this->makeItem('SP-'.uniqid(), 'SPAREPART');
        StockService::move($this->wh->id, $item->id, 'PURCHASE', 100, 0, $this->co->id, $this->site->id, null, 'OPENING', 'OP-1', 1000, today()->toDateString());
        StockService::reserve($this->wh->id, $item->id, 'DO', 55, 'DO-55', 80);

        $ledger = StockService::move($this->wh->id, $item->id, 'SALE', 0, 50, $this->co->id, $this->site->id, 55, 'DO', 'DO-55');

        $this->assertSame(50.0, (float) StockService::balance($this->wh->id, $item->id));
        $this->assertNotNull($ledger);
    }

    public function test_move_rejects_company_mismatch_with_warehouse(): void
    {
        $otherCo = Company::create(['code' => 'OTH', 'name' => 'Other Co', 'status' => true]);
        $item = $this->makeItem('SP-'.uniqid(), 'SPAREPART');

        $this->expectException(\InvalidArgumentException::class);

        StockService::move($this->wh->id, $item->id, 'PURCHASE', 10, 0, $otherCo->id, null, null, 'OPENING', 'OP-X', 1000, today()->toDateString());
    }

    public function test_master_wh_lookup_prefers_warehouse_company(): void
    {
        $item = $this->makeItem('SP-'.uniqid(), 'SPAREPART');
        // no explicit company → resolved from the warehouse, not arbitrary
        $ledger = StockService::move($this->wh->id, $item->id, 'PURCHASE', 10, 0, null, $this->site->id, null, 'OPENING', 'OP-X', 1000, today()->toDateString());

        $this->assertSame($this->co->id, $ledger->company_id);
    }

    public function test_do_consumed_ticket_rejected_for_dispatch_link(): void
    {
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);
        $trip = DispatchTrip::create([
            'number' => 'DSP-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'trip_date' => today(), 'status' => 'PLANNED', 'created_by' => $this->admin->id,
        ]);

        $this->expectException(\DomainException::class);

        DispatchService::linkTicket($trip, $flow['ticket']);
    }

    public function test_dispatch_rejects_unserviceable_truck(): void
    {
        $truck = Equipment::create([
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'code' => 'DT-'.uniqid(), 'name' => 'Broken Truck', 'type' => 'DUMP_TRUCK',
            'unit_id' => Unit::firstOrCreate(['code' => 'TSTU'], ['name' => 'Ton'])->id,
            'status' => 'BREAKDOWN',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('BREAKDOWN');

        DispatchService::assign([
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'trip_date' => today()->toDateString(), 'truck_id' => $truck->id,
        ]);
    }

    public function test_dispatch_duplicate_without_shift_blocked(): void
    {
        $truck = Equipment::create([
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'code' => 'DT-'.uniqid(), 'name' => 'Truck', 'type' => 'DUMP_TRUCK',
            'unit_id' => Unit::firstOrCreate(['code' => 'TSTU'], ['name' => 'Ton'])->id,
            'status' => 'AVAILABLE',
        ]);
        $data = [
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'trip_date' => today()->toDateString(), 'truck_id' => $truck->id,
        ];
        DispatchService::assign($data);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('sudah memiliki trip');

        DispatchService::assign($data);
    }

    public function test_do_complete_audits_and_marks_ticket_posted(): void
    {
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);

        $flow['ticket']->refresh();
        $this->assertSame('POSTED', $flow['ticket']->status);
        $this->assertDatabaseHas('audit_logs', ['module' => 'SALES', 'action' => 'POST']);
    }
}
