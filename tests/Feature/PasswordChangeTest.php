<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $user = User::where('username', 'superadmin')->first();

        $resp = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'Admin!2345',
            'password' => 'NewPass!4567',
            'password_confirmation' => 'NewPass!4567',
        ]);

        $resp->assertRedirect('/dashboard');
        $this->assertTrue(Hash::check('NewPass!4567', $user->fresh()->password));
    }

    public function test_wrong_current_password_rejected(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $user = User::where('username', 'superadmin')->first();

        $resp = $this->from('/profile/security')->actingAs($user)->put('/profile/password', [
            'current_password' => 'WrongPass!99',
            'password' => 'NewPass!4567',
            'password_confirmation' => 'NewPass!4567',
        ]);

        $resp->assertRedirect('/profile/security');
        $resp->assertSessionHasErrors('current_password');
    }

    public function test_locked_user_cannot_login(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $user = User::where('username', 'superadmin')->first();
        $user->update(['status' => 'LOCKED']);

        $resp = $this->post('/login', ['email' => 'superadmin', 'password' => 'Admin!2345']);
        $resp->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
