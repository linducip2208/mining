<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewModulesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $this->seed(\Database\Seeders\AccountingSeeder::class);
        $this->admin = User::where('username', 'superadmin')->first();
    }

    public function test_new_module_pages_render(): void
    {
        $urls = [
            // fleet
            '/fleet', '/fleet/availability', '/fleet/utilization', '/fleet/downtime', '/fleet/cost',
            '/fleet/meters', '/fleet/inspections', '/fleet/assignments',
            '/equipment-categories', '/equipment-categories/create',
            '/vehicles', '/vehicles/create',
            // fuel
            '/fuel', '/fuel/stock', '/fuel/consumption', '/fuel/variance',
            '/fuel-tanks', '/fuel-tanks/create',
            '/fuel-issues', '/fuel-issues/create',
            '/fuel-receipts', '/fuel-receipts/create',
            '/fuel-transfers', '/fuel-transfers/create',
            '/fuel-dips',
            // tire
            '/tires', '/tires/create',
            // dispatch & stockpile
            '/dispatch', '/dispatch/trips', '/dispatch/trips/create',
            '/loading-points', '/loading-points/create',
            '/dumping-points', '/dumping-points/create',
            '/hauling-routes', '/hauling-routes/create',
            '/stockpiles', '/stockpiles/dashboard', '/stockpiles/create',
            // quality
            '/quality-parameters', '/quality-parameters/create',
            '/specs', '/specs/create',
            '/samples', '/samples/create',
            '/quality-holds',
            // cost & contract & budget
            '/cost', '/cost/others',
            '/customer-contracts', '/customer-contracts/create',
            '/supplier-contracts', '/supplier-contracts/create',
            '/hauling-contracts', '/hauling-contracts/create',
            '/budgets', '/budgets/create',
            // hse & compliance & fiscal
            '/hse', '/hse/reports', '/hse/reports/create', '/hse/permits', '/hse/activities',
            '/compliance', '/compliance/calendar', '/compliance/create',
            '/fiscal-periods',
            // telematics, weighbridge devices, ai, forecast, executive
            '/telematics', '/weighbridge/devices', '/ai', '/forecast', '/executive',
        ];

        foreach ($urls as $url) {
            $resp = $this->actingAs($this->admin)->get($url);
            $this->assertEquals(200, $resp->status(), "GET {$url}");
        }
    }

    public function test_weighbridge_api_requires_token(): void
    {
        $this->postJson('/api/weighbridge/reading', ['raw_weight' => 100])
            ->assertStatus(401);
    }

    public function test_viewer_cannot_post_fuel_receipt(): void
    {
        $viewer = User::create([
            'name' => 'Viewer', 'username' => 'viewer2', 'email' => 'viewer2@test.local',
            'password' => bcrypt('Viewer!2345'), 'status' => 'ACTIVE',
        ]);
        $viewer->roles()->sync([\App\Models\Role::where('code', 'VIEWER')->first()->id]);

        $resp = $this->actingAs($viewer)->post('/fuel-receipts', []);
        $this->assertEquals(403, $resp->status());
    }
}
