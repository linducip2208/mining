<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;

class DataScopeSparepartTest extends AdminFlowTestCase
{
    public function test_cross_company_warehouse_rejected(): void
    {
        $other = Company::create(['code' => 'OTH', 'name' => 'Other Co', 'status' => true]);
        $otherWh = Warehouse::create(['company_id' => $other->id, 'code' => 'OTH-WH', 'name' => 'Other WH']);
        $item = $this->makeSparepart('SPR-070');

        $role = Role::create(['code' => 'TST_SP', 'name' => 'Test SP']);
        $role->permissions()->sync(Permission::whereIn('code', ['sparepart.view', 'sparepart_receipt.view', 'sparepart_receipt.create'])->pluck('id'));
        $user = User::create(['name' => 'SP User', 'username' => 'spuser1', 'email' => 'sp1@test.local', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        $user->roles()->sync([$role->id => ['scope' => 'COMPANY', 'company_id' => $this->co->id]]);

        $this->actingAs($user)->post('/sparepart/receipt', [
            'item_id' => $item->id, 'warehouse_id' => $otherWh->id,
            'qty' => 5, 'condition' => 'BAIK', 'receipt_date' => today()->toDateString(),
        ])->assertForbidden();
    }
}
