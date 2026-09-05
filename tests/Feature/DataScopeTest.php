<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\MiningActivity;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $c1;
    protected Company $c2;
    protected Site $siteA;
    protected Site $siteB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $this->c1 = Company::create(['code' => 'DSA', 'name' => 'Scope A', 'status' => true]);
        $this->c2 = Company::create(['code' => 'DSB', 'name' => 'Scope B', 'status' => true]);
        $this->siteA = Site::create(['company_id' => $this->c1->id, 'code' => 'DSA-A', 'name' => 'Site A', 'type' => 'MINE']);
        $this->siteB = Site::create(['company_id' => $this->c1->id, 'code' => 'DSA-B', 'name' => 'Site B', 'type' => 'MINE']);
    }

    protected function makeScopedUser(string $roleCode, string $scope, ?int $companyId, ?int $siteId): User
    {
        $user = User::create([
            'name' => 'Scoped', 'username' => 'scoped_' . strtolower($roleCode), 'email' => 'scoped_' . strtolower($roleCode) . '@test.local',
            'password' => bcrypt('Scoped!2345'), 'status' => 'ACTIVE',
        ]);
        $role = Role::where('code', $roleCode)->first();
        $user->roles()->attach($role->id, ['scope' => $scope, 'company_id' => $companyId, 'site_id' => $siteId]);
        return $user;
    }

    public function test_site_scope_filters_mining_activities(): void
    {
        MiningActivity::create(['number' => 'MA-SCOPE-A', 'company_id' => $this->c1->id, 'site_id' => $this->siteA->id, 'date' => today(), 'tonnage' => 100, 'status' => 'APPROVED']);
        MiningActivity::create(['number' => 'MA-SCOPE-B', 'company_id' => $this->c1->id, 'site_id' => $this->siteB->id, 'date' => today(), 'tonnage' => 100, 'status' => 'APPROVED']);

        $user = $this->makeScopedUser('MINE_MANAGER', 'SITE', $this->c1->id, $this->siteA->id);

        $resp = $this->actingAs($user)->get('/mining-activities');
        $resp->assertStatus(200);
        $resp->assertSee('MA-SCOPE-A');
        $resp->assertDontSee('MA-SCOPE-B');
    }

    public function test_company_scope_filters_sales_orders(): void
    {        $customer = \App\Models\Customer::create(['company_id' => $this->c1->id, 'code' => 'DSC-A', 'name' => 'Scope Customer A']);
        SalesOrder::create(['number' => 'SO-SCOPE-A', 'company_id' => $this->c1->id, 'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED', 'created_by' => 1]);
        SalesOrder::create(['number' => 'SO-SCOPE-B', 'company_id' => $this->c2->id, 'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED', 'created_by' => 1]);

        $user = $this->makeScopedUser('SALES_MANAGER', 'COMPANY', $this->c1->id, null);

        $resp = $this->actingAs($user)->get('/sales-orders');
        $resp->assertStatus(200);
        $resp->assertSee('SO-SCOPE-A');
        $resp->assertDontSee('SO-SCOPE-B');
    }

    public function test_idor_blocked_on_direct_record_urls(): void
    {
        $a = MiningActivity::create(['number' => 'MA-IDOR-A', 'company_id' => $this->c1->id, 'site_id' => $this->siteA->id, 'date' => today(), 'tonnage' => 50, 'status' => 'APPROVED']);
        $b = MiningActivity::create(['number' => 'MA-IDOR-B', 'company_id' => $this->c1->id, 'site_id' => $this->siteB->id, 'date' => today(), 'tonnage' => 50, 'status' => 'APPROVED']);

        $user = $this->makeScopedUser('MINE_MANAGER', 'SITE', $this->c1->id, $this->siteA->id);

        // own site: allowed
        $this->actingAs($user)->get('/mining-activities/' . $a->id)->assertStatus(200);
        // other site by direct ID: forbidden
        $this->actingAs($user)->get('/mining-activities/' . $b->id)->assertStatus(403);
    }

    public function test_idor_blocked_on_invoice_and_so_urls(): void
    {
        $customer = \App\Models\Customer::create(['company_id' => $this->c1->id, 'code' => 'DSC-IDOR', 'name' => 'IDOR Customer']);
        $soA = SalesOrder::create(['number' => 'SO-IDOR-A', 'company_id' => $this->c1->id, 'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED', 'created_by' => 1]);
        $soB = SalesOrder::create(['number' => 'SO-IDOR-B', 'company_id' => $this->c2->id, 'customer_id' => $customer->id, 'order_date' => today(), 'status' => 'APPROVED', 'created_by' => 1]);

        $user = $this->makeScopedUser('SALES_MANAGER', 'COMPANY', $this->c1->id, null);

        $this->actingAs($user)->get('/sales-orders/' . $soA->id)->assertStatus(200);
        $this->actingAs($user)->get('/sales-orders/' . $soB->id)->assertStatus(403);
    }

    public function test_cross_scope_create_blocked(): void
    {
        $user = $this->makeScopedUser('MINE_MANAGER', 'SITE', $this->c1->id, $this->siteA->id);

        // attempt to create mining activity in another site
        $resp = $this->actingAs($user)->post('/mining-activities', [
            'company_id' => $this->c1->id,
            'site_id' => $this->siteB->id,
            'date' => today()->toDateString(),
            'activity_type' => 'MINING',
            'tonnage' => 10,
        ]);
        $this->assertEquals(403, $resp->status());
    }
}
