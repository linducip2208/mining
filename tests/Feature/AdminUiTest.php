<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UI shell regression: layout baru, dark toggle, cmdk, error pages.
 */
class AdminUiTest extends TestCase
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

    public function test_shell_chrome_present(): void
    {
        $html = $this->actingAs($this->admin)->get('/dashboard')->getContent();
        foreach (['id="sidebar"', 'id="cmdk"', 'toggleTheme()', 'id="mainContent"', 'Lewati ke konten', 'sb-collapsed', 'Dokumentasi'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_key_pages_render_with_new_components(): void
    {
        foreach (['/dashboard', '/users', '/users/create', '/roles', '/mining-dashboard', '/fuel', '/fleet', '/stockpiles/dashboard'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertStatus(200);
        }
        $this->actingAs($this->admin)->get(route('approval.index', [], false))->assertStatus(200);
    }

    public function test_error_pages_branded(): void
    {
        foreach ([403, 404, 419, 500, 503] as $code) {
            $html = view('errors.layout', ['code' => $code, 'exception' => null])->render();
            $this->assertStringContainsString('Mining ERP', $html);
            $this->assertStringContainsString((string) $code, $html);
            $this->assertStringNotContainsString('href="#"', $html);
        }
    }

    public function test_dark_mode_classes_present_in_shell(): void
    {
        $html = $this->actingAs($this->admin)->get('/dashboard')->getContent();
        $this->assertStringContainsString('dark:', $html);
        $this->assertStringContainsString('docs-theme', $this->get('/docs')->getContent());
    }
}
