<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\LetterRegister;
use App\Models\LetterType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\CoreSeeder;
use Database\Seeders\LetterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AdminFlowTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Company $co;

    protected Site $site;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);
        $this->seed(AccountingSeeder::class);
        $this->seed(LetterSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
        $this->actingAs($this->admin);
        $this->co = Company::create(['code' => 'ADM', 'name' => 'Admin Test Co', 'status' => true]);
        $this->site = Site::create(['company_id' => $this->co->id, 'code' => 'ADM-S', 'name' => 'ADM Site', 'type' => 'MINE']);
        $this->warehouse = Warehouse::create(['company_id' => $this->co->id, 'site_id' => $this->site->id, 'code' => 'ADM-WH', 'name' => 'ADM Warehouse']);
    }

    protected function makeSparepart(string $code, array $over = []): Item
    {
        $cat = ItemCategory::firstOrCreate(['code' => 'SPR'], ['name' => 'Sparepart', 'type' => 'SPAREPART']);
        $unit = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pcs']);

        return Item::create(array_merge([
            'code' => $code, 'name' => 'Sparepart '.$code,
            'item_category_id' => $cat->id, 'type' => 'SPAREPART', 'unit_id' => $unit->id,
            'min_stock' => 10, 'reorder_point' => 20, 'max_stock' => 100,
            'standard_cost' => 50000, 'status' => true,
        ], $over));
    }

    protected function makeLetter(array $over = []): LetterRegister
    {
        $type = LetterType::where('code', 'SP')->first();

        return LetterRegister::create(array_merge([
            'letter_type_id' => $type->id, 'letter_date' => today()->toDateString(),
            'subject' => 'Penawaran unit', 'recipient_type' => 'CUSTOMER',
            'recipient_name' => 'PT Maju', 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ], $over));
    }

    protected function makeCustomer(): Customer
    {
        return Customer::create(['company_id' => $this->co->id, 'code' => 'C-'.uniqid(), 'name' => 'PT Maju']);
    }

    protected function makeSupplier(): Supplier
    {
        return Supplier::create(['company_id' => $this->co->id, 'code' => 'S-'.uniqid(), 'name' => 'PT Supply']);
    }

    protected function makeEmployee(): Employee
    {
        return Employee::create(['code' => 'E-'.uniqid(), 'name' => 'Budi', 'company_id' => $this->co->id, 'site_id' => $this->site->id]);
    }
}
