<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_returns_valid_installable_payload_with_default_icons(): void
    {
        $this->seed(CoreSeeder::class);

        $response = $this->get('/manifest.webmanifest');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');

        $payload = $response->json();
        $this->assertSame('/dashboard', $payload['start_url']);
        $this->assertNotEmpty($payload['name']);
        $this->assertNotEmpty($payload['short_name']);

        $sizes = collect($payload['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertStringContainsString(
            '/icons/icon-192.png',
            collect($payload['icons'])->firstWhere('sizes', '192x192')['src']
        );
    }

    public function test_manifest_uses_standalone_display_when_pwa_enabled(): void
    {
        $this->seed(CoreSeeder::class);
        Setting::updateOrCreate(['key' => 'pwa.enabled'], ['value' => '1', 'type' => 'boolean']);

        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('display', 'standalone');
    }

    public function test_offline_page_is_public_and_self_contained(): void
    {
        $response = $this->get('/offline');

        $response->assertOk()->assertSee('Anda sedang offline');
    }

    public function test_service_worker_and_icons_exist_on_disk(): void
    {
        foreach (['sw.js', 'icons/icon-192.png', 'icons/icon-512.png', 'icons/maskable-512.png', 'icons/apple-touch-icon.png'] as $file) {
            $this->assertFileExists(public_path($file), "Missing public/{$file}");
        }
    }
}
