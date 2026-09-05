<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SidebarRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every route referenced in the sidebar config must exist.
     * Guards against menus silently degrading to "#".
     */
    public function test_all_sidebar_routes_exist(): void
    {
        $content = file_get_contents(resource_path('views/layouts/partials/sidebar.blade.php'));
        preg_match_all("/'route'\s*=>\s*'([^']+)'/", $content, $m);
        $routes = array_unique($m[1]);

        $this->assertNotEmpty($routes, 'Sidebar tidak berisi menu.');
        $this->assertGreaterThanOrEqual(55, count($routes), 'Jumlah menu sidebar menyusut tak terduga.');

        $missing = [];
        foreach ($routes as $name) {
            if (!Route::has($name)) {
                $missing[] = $name;
            }
        }
        $this->assertEmpty($missing, 'Route sidebar hilang: ' . implode(', ', $missing));
    }

    /**
     * Rendered sidebar (as super admin) must not contain href="#".
     */
    public function test_rendered_sidebar_has_no_hash_links(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $admin = User::where('username', 'superadmin')->first();

        $resp = $this->actingAs($admin)->get('/dashboard');
        $resp->assertStatus(200);
        $this->assertStringNotContainsString('href="#"', $resp->getContent());
    }

    public function test_user_management_pages_render(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $admin = User::where('username', 'superadmin')->first();

        $this->actingAs($admin)->get('/users')->assertStatus(200);
        $this->actingAs($admin)->get('/users/create')->assertStatus(200);
        $this->actingAs($admin)->get('/users/' . $admin->id . '/edit')->assertStatus(200);
        $this->actingAs($admin)->get('/users/' . $admin->id . '/login-history')->assertStatus(200);
    }

    public function test_role_management_crud_and_matrix(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $admin = User::where('username', 'superadmin')->first();

        $this->actingAs($admin)->get('/roles')->assertStatus(200);
        $this->actingAs($admin)->get('/roles/create')->assertStatus(200);

        $resp = $this->actingAs($admin)->post('/roles', [
            'code' => 'TEST_ROLE', 'name' => 'Test Role', 'description' => 'x',
        ]);
        $resp->assertRedirect();
        $role = Role::where('code', 'TEST_ROLE')->first();
        $this->assertNotNull($role);

        $this->actingAs($admin)->get('/roles/' . $role->id)->assertStatus(200);

        $perms = Permission::whereIn('code', ['dashboard.view', 'report.view'])->pluck('id')->all();
        $resp2 = $this->actingAs($admin)->put('/roles/' . $role->id . '/permissions', ['permissions' => $perms]);
        $resp2->assertRedirect();
        $this->assertEqualsCanonicalizing($perms, $role->fresh()->permissions->pluck('id')->all());

        $resp3 = $this->actingAs($admin)->delete('/roles/' . $role->id);
        $resp3->assertRedirect();
        $this->assertNull(Role::where('code', 'TEST_ROLE')->first());
    }
}
