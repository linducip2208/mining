<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_h1_price_and_whatsapp_cta(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $page = SeoPage::where('path', 'source-code-erp-mining')->firstOrFail();
        $response = $this->get('/source-code-erp-mining');

        $response->assertOk()
            ->assertSee($page->h1, false)
            ->assertSee('Rp12.000.000', false)
            ->assertSee('Rp12 Juta', false)
            ->assertSee('wa.me/6281296052010', false);
    }

    public function test_unknown_path_returns_404(): void
    {
        SeoTestHelper::seedCatalog($this);

        $this->get('/jalur-yang-tidak-ada-xyz')->assertNotFound();
    }

    public function test_commercial_positioning_present_on_pillars(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $this->get('/source-code-erp-tambang')->assertOk()
            ->assertSee('Source Code ERP Tambang Mulai Rp12 Juta', false);
    }
}
