<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;

class SparepartPermissionTest extends AdminFlowTestCase
{
    public function test_guest_and_unauthorized_blocked(): void
    {
        auth()->logout();
        $this->get('/sparepart')->assertRedirect('/login');

        $viewer = User::create(['name' => 'Viewer', 'username' => 'spview1', 'email' => 'spv@test.local', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        $viewer->roles()->sync([Role::where('code', 'VIEWER')->first()->id]);

        $this->actingAs($viewer)->post('/sparepart/master', ['code' => 'X'])->assertForbidden();
        $this->actingAs($viewer)->get('/sparepart')->assertOk();
    }
}
