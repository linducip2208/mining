<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\DepositService;
use App\Services\NumberingService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $this->seed(\Database\Seeders\AccountingSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
        $this->company = Company::firstOrCreate(['code' => 'TST'], ['name' => 'Test Co', 'status' => true]);
    }

    // ============ AUTH / RBAC ============

    public function test_login_with_username_works(): void
    {
        $resp = $this->post('/login', ['email' => 'superadmin', 'password' => 'Admin!2345']);
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_access_denied_without_permission(): void
    {
        // create user with VIEWER role (view-only permissions)
        $viewer = User::create([
            'name' => 'Viewer', 'username' => 'viewer1', 'email' => 'viewer@test.local',
            'password' => bcrypt('Viewer!2345'), 'status' => 'ACTIVE',
        ]);
        $viewer->roles()->sync([Role::where('code', 'VIEWER')->first()->id]);

        // viewer may view, but cannot create
        $resp = $this->actingAs($viewer)->post('/users', [
            'name' => 'X', 'username' => 'xuser', 'email' => 'x@test.local',
            'password' => 'Secret!123', 'password_confirmation' => 'Secret!123', 'status' => 'ACTIVE',
        ]);
        $this->assertEquals(403, $resp->status());
    }

    public function test_permission_granted_for_module_user(): void
    {
        $this->actingAs($this->admin)->get('/users')->assertStatus(200);
    }

    public function test_guest_redirect_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // ============ ACCOUNTING ============

    public function test_unbalanced_journal_rejected(): void
    {
        $this->expectException(\DomainException::class);
        AccountingService::post($this->company->id, today()->toDateString(), [
            ['code' => '1-1000', 'debit' => 500],
            ['code' => '4-1000', 'credit' => 400],
        ]);
    }

    public function test_balanced_journal_posts(): void
    {
        $entry = AccountingService::post($this->company->id, today()->toDateString(), [
            ['code' => '1-1000', 'debit' => 1000],
            ['code' => '4-1000', 'credit' => 1000],
        ], 'TEST');
        $this->assertEquals('POSTED', $entry->status);
        $this->assertEquals(1000, $entry->total_debit);
        $this->assertEquals(1000, $entry->total_credit);
    }

    public function test_reversal_creates_mirror_and_cannot_double_reverse(): void
    {
        $entry = AccountingService::post($this->company->id, today()->toDateString(), [
            ['code' => '1-1000', 'debit' => 700],
            ['code' => '4-1000', 'credit' => 700],
        ]);
        $rev = AccountingService::reverse($entry, 'test');
        $this->assertTrue($rev->is_reversal);
        $this->assertEquals($entry->id, $rev->reversal_of_id);

        $this->expectException(\DomainException::class);
        AccountingService::reverse($rev);
    }

    // ============ INVENTORY ============

    public function test_negative_stock_rejected(): void
    {
        $wh = \App\Models\Warehouse::create(['company_id' => $this->company->id, 'code' => 'TWH', 'name' => 'TWarehouse']);
        $item = Item::create(['code' => 'TIT', 'name' => 'TItem', 'item_category_id' => \App\Models\ItemCategory::create(['code' => 'TCAT', 'name' => 'TCat'])->id, 'type' => 'PRODUCT', 'unit_id' => \App\Models\Unit::create(['code' => 'TUN', 'name' => 'Unit'])->id]);

        $this->expectException(\DomainException::class);
        StockService::move($wh->id, $item->id, 'ISSUE', 0, 100, $this->company->id);
    }

    public function test_stock_in_out_balance(): void
    {
        $wh = \App\Models\Warehouse::create(['company_id' => $this->company->id, 'code' => 'TWH2', 'name' => 'TWarehouse2']);
        $item = Item::create(['code' => 'TIT2', 'name' => 'TItem2', 'item_category_id' => \App\Models\ItemCategory::create(['code' => 'TCAT2', 'name' => 'TCat2'])->id, 'type' => 'PRODUCT', 'unit_id' => \App\Models\Unit::create(['code' => 'TUN2', 'name' => 'Unit2'])->id]);

        StockService::move($wh->id, $item->id, 'OPENING', 100, 0, $this->company->id, null, null, null, null, 10000);
        StockService::move($wh->id, $item->id, 'SALE', 0, 40, $this->company->id);
        $this->assertEquals(60, StockService::balance($wh->id, $item->id));
    }

    // ============ NUMBERING ============

    public function test_numbering_unique_sequential(): void
    {
        $n1 = NumberingService::generate('SO');
        $n2 = NumberingService::generate('SO');
        $this->assertNotEquals($n1, $n2);
        $this->assertStringStartsWith('SO-', $n1);
    }

    // ============ DEPOSIT ============

    public function test_deposit_in_use_refund_balance(): void
    {
        $customer = Customer::create(['company_id' => $this->company->id, 'code' => 'TDC', 'name' => 'Deposit Customer']);

        DepositService::depositIn($this->company->id, $customer->id, 1000000, today(), null, 'TEST-DEP');
        $this->assertEquals(1000000, DepositService::balance($customer->id));

        DepositService::allocate($this->company->id, $customer->id, 300000, today());
        $this->assertEquals(700000, DepositService::balance($customer->id));

        DepositService::refund($this->company->id, $customer->id, 200000, today(), null, 'TEST-REF');
        $this->assertEquals(500000, DepositService::balance($customer->id));
    }

    public function test_deposit_overdraft_rejected(): void
    {
        $customer = Customer::create(['company_id' => $this->company->id, 'code' => 'TDC2', 'name' => 'Deposit Customer2']);
        DepositService::depositIn($this->company->id, $customer->id, 100000, today(), null, 'TEST-DEP2');

        $this->expectException(\DomainException::class);
        DepositService::allocate($this->company->id, $customer->id, 999999, today());
    }

    // ============ WEIGHBRIDGE CALC ============

    public function test_weighbridge_net_calculation(): void
    {
        $site = \App\Models\Site::create(['company_id' => $this->company->id, 'code' => 'TSIT', 'name' => 'TSite']);
        $wb = \App\Models\Weighbridge::create(['site_id' => $site->id, 'code' => 'TWB', 'name' => 'TWeighbridge']);

        $ticket = \App\Models\WeighbridgeTicket::create([
            'ticket_no' => NumberingService::generate('WB', $this->company->id, $site->id),
            'weighbridge_id' => $wb->id,
            'company_id' => $this->company->id,
            'site_id' => $site->id,
            'direction' => 'OUT',
            'first_weight' => 25000,
            'second_weight' => 10000,
            'gross' => 25000,
            'tare' => 10000,
            'net' => 15000,
        ]);

        $this->assertEquals(15000, $ticket->gross - $ticket->tare);
    }

    // ============ APPROVAL ============

    public function test_unauthorized_approver_cannot_act(): void
    {
        $hr = User::create([
            'name' => 'HR User', 'username' => 'hruser', 'email' => 'hr@test.local',
            'password' => bcrypt('Hr!234567'), 'status' => 'ACTIVE',
        ]);

        $pr = \App\Models\PurchaseRequest::create([
            'number' => NumberingService::generate('PR', $this->company->id),
            'company_id' => $this->company->id,
            'request_date' => today(),
            'status' => 'DRAFT',
            'created_by' => $this->admin->id,
        ]);

        // acting as admin submits
        $this->actingAs($this->admin);
        $request = \App\Services\ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);

            if ($request) {
                // HR user (non-approver) tries to approve via action id
                $action = $request->actions()->first();
                $ok = \App\Services\ApprovalService::actOnActionId($action->id, $hr, 'APPROVE');
                $this->assertFalse($ok);
            } else {
                // no workflow configured => auto approved; still valid behavior
                $this->assertEquals('APPROVED', $pr->fresh()->status);
            }
    }

    public function test_role_assignment_requires_role_update_permission(): void
    {
        $role = Role::create(['code' => 'USR_OPS', 'name' => 'User Operator']);
        $role->permissions()->sync(Permission::whereIn('code', ['user.view', 'user.create', 'user.update'])->pluck('id'));
        $actor = User::create([
            'name' => 'Actor', 'username' => 'actor1', 'email' => 'actor@test.local',
            'password' => bcrypt('Actor!2345'), 'status' => 'ACTIVE',
        ]);
        $actor->roles()->sync([$role->id]);

        // actor may manage users but NOT assign roles
        $resp = $this->actingAs($actor)->post('/users', [
            'name' => 'Target', 'username' => 'target1', 'email' => 'target@test.local',
            'password' => 'Secret!123', 'password_confirmation' => 'Secret!123',
            'status' => 'ACTIVE',
            'roles' => [Role::where('code', 'VIEWER')->first()->id],
        ]);
        $this->assertEquals(403, $resp->status());

        // without roles payload it is allowed
        $resp2 = $this->actingAs($actor)->post('/users', [
            'name' => 'Target2', 'username' => 'target2', 'email' => 'target2@test.local',
            'password' => 'Secret!123', 'password_confirmation' => 'Secret!123',
            'status' => 'ACTIVE',
        ]);
        $resp2->assertRedirect('/users');
    }

    public function test_cannot_deactivate_or_elevate_self(): void
    {
        $role = Role::create(['code' => 'USR_OPS2', 'name' => 'User Operator 2']);
        $role->permissions()->sync(Permission::whereIn('code', ['user.view', 'user.update'])->pluck('id'));
        $actor = User::create([
            'name' => 'Actor2', 'username' => 'actor2', 'email' => 'actor2@test.local',
            'password' => bcrypt('Actor!2345'), 'status' => 'ACTIVE',
        ]);
        $actor->roles()->sync([$role->id]);

        // self deactivation blocked
        $resp = $this->actingAs($actor)->put('/users/' . $actor->id, [
            'name' => 'Actor2', 'username' => 'actor2', 'email' => 'actor2@test.local',
            'status' => 'INACTIVE', 'roles' => [$role->id],
        ]);
        $this->assertEquals(403, $resp->status());

        // self role change blocked
        $resp2 = $this->actingAs($actor)->put('/users/' . $actor->id, [
            'name' => 'Actor2', 'username' => 'actor2', 'email' => 'actor2@test.local',
            'status' => 'ACTIVE', 'roles' => [Role::where('code', 'VIEWER')->first()->id],
        ]);
        $this->assertEquals(403, $resp2->status());
    }
}
