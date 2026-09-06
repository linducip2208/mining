<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_settings_page_shows_human_labels_only(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        Setting::create(['key' => 'payroll.pph21_rate', 'value' => '5', 'type' => 'percentage']);
        $admin = User::where('username', 'superadmin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/settings');
        $response->assertOk()->assertSee('Tarif PPh 21');

        $visibleText = strip_tags($response->getContent());
        $this->assertStringNotContainsString('payroll.pph21_rate', $visibleText);
        $this->assertStringNotContainsString('developer_labels_enabled', $visibleText);
    }
}
