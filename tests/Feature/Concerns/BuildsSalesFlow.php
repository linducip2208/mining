<?php

namespace Tests\Feature\Concerns;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Weighbridge;
use App\Models\WeighbridgeTicket;
use App\Services\StockService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Shared builder for COGS/sales-flow tests: item + SO + DO + weighbridge ticket.
 */
trait BuildsSalesFlow
{
    use RefreshDatabase;

    protected User $admin;

    protected Company $co;

    protected Site $site;

    protected Warehouse $wh;

    protected function seedBase(): void
    {
        $this->seed(CoreSeeder::class);
        $this->seed(AccountingSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
        $this->actingAs($this->admin);
        $this->co = Company::create(['code' => 'TST', 'name' => 'Test Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'TST-S', 'name' => 'Test Site', 'type' => 'MINE']);
        $this->wh = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'TST-W', 'name' => 'Test WH']);
    }

    protected function makeItem(string $code, string $type = 'PRODUCT', float $avg = 0): Item
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'TST-'.$type], ['name' => 'TST '.$type, 'type' => $type]);
        $unit = Unit::firstOrCreate(['code' => 'TSTU'], ['name' => 'Ton']);

        return Item::create([
            'code' => $code, 'name' => 'TST '.$code,
            'item_category_id' => $cat->id, 'type' => $type, 'unit_id' => $unit->id,
            'avg_cost' => $avg, 'standard_cost' => $avg,
        ]);
    }

    protected function makeCustomer(): Customer
    {
        return Customer::create(['company_id' => $this->co->id, 'code' => 'C-'.uniqid(), 'name' => 'PT Test '.uniqid()]);
    }

    /**
     * SO(10 @20000) + DO + WB ticket net 5. Stock-in optional.
     */
    protected function buildFlow(array $over = []): array
    {
        $stockQty = $over['stockQty'] ?? 0;
        $stockCost = $over['stockCost'] ?? 0;
        $gross = $over['gross'] ?? 10;
        $tare = $over['tare'] ?? 5;

        $item = $this->makeItem($over['itemCode'] ?? 'FG-'.uniqid(), $over['itemType'] ?? 'PRODUCT');
        if ($stockQty > 0) {
            StockService::move($this->wh->id, $item->id, 'PURCHASE', $stockQty, 0, $this->co->id, $this->site->id, null, 'OPENING', 'OPEN-'.uniqid(), $stockCost, today()->toDateString());
        }

        $customer = $this->makeCustomer();
        $so = SalesOrder::create([
            'number' => 'SO-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED',
            'subtotal' => 200000, 'tax_amount' => 22000, 'total' => 222000,
            'created_by' => $this->admin->id,
        ]);
        $so->items()->create(['item_id' => $item->id, 'qty' => 10, 'qty_delivered' => 0, 'unit_price' => 20000, 'total_price' => 200000]);

        $do = DeliveryOrder::create([
            'number' => 'DO-'.uniqid(), 'sales_order_id' => $so->id, 'warehouse_id' => $this->wh->id,
            'delivery_date' => today(), 'total_qty' => 10, 'status' => 'DRAFT',
            'created_by' => $this->admin->id,
        ]);
        $do->items()->create(['item_id' => $item->id, 'qty_ordered' => 10, 'qty_delivered' => 0]);

        $wb = Weighbridge::create(['site_id' => $this->site->id, 'code' => 'WB-'.uniqid(), 'name' => 'WB Test']);
        $ticket = WeighbridgeTicket::create([
            'ticket_no' => 'WB-T-'.uniqid(), 'weighbridge_id' => $wb->id, 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'direction' => 'OUT',
            'first_weight' => 1000 + $gross, 'second_weight' => 1000 + $tare,
            'gross' => $gross, 'tare' => $tare, 'net' => $gross - $tare,
            'customer_id' => $customer->id, 'item_id' => $item->id,
            'status' => 'COMPLETE', 'created_by' => $this->admin->id,
        ]);

        return ['item' => $item, 'customer' => $customer, 'so' => $so, 'do' => $do, 'ticket' => $ticket];
    }
}
