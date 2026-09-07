<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ErrorPageResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_renders_without_settings_table(): void
    {
        Schema::dropIfExists('settings');

        $this->get('/halaman-yang-tidak-ada-xyz')->assertNotFound();
    }

    public function test_403_renders_without_settings_table(): void
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        Schema::dropIfExists('settings');

        // /docs/health requires superadmin → 403 for guest, rendered via errors layout
        $this->get('/docs/health')->assertForbidden();
    }
}
