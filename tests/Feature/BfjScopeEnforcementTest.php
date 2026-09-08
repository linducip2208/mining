<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\LegacyImportBatch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BfjScopeEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $username, string $roleCode, string $scope, ?int $companyId): User
    {
        $user = User::create([
            'name' => $username, 'username' => $username, 'email' => $username.'@test.local',
            'password' => bcrypt('Secret!2345'), 'status' => 'ACTIVE',
        ]);
        $role = Role::where('code', $roleCode)->firstOrFail();
        $role->permissions()->syncWithoutDetaching(
            Permission::whereIn('code', ['legacy_import.review', 'legacy_import.execute'])->pluck('id')->all()
        );
        $user->roles()->attach($role->id, ['scope' => $scope, 'company_id' => $companyId, 'site_id' => null]);

        return $user;
    }

    public function test_cross_company_user_cannot_view_batch(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $a = Company::create(['code' => 'BFJA', 'name' => 'BFJ A', 'status' => true]);
        $b = Company::create(['code' => 'BFJB', 'name' => 'BFJ B', 'status' => true]);
        $batch = LegacyImportBatch::create(['file_name' => 'x.xlsx', 'file_hash' => uniqid(), 'mode' => 'HISTORY_ONLY', 'status' => 'SCANNED', 'company_id' => $a->id]);

        $userB = $this->makeUser('bfj_b', 'SALES_MANAGER', 'COMPANY', $b->id);
        $this->actingAs($userB)->get('/bfj-imports/'.$batch->id)->assertStatus(403);

        $userA = $this->makeUser('bfj_a', 'SALES_MANAGER', 'COMPANY', $a->id);
        $this->actingAs($userA)->get('/bfj-imports/'.$batch->id)->assertStatus(200);
    }
}
