<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoPageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoResponsiveSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Structural responsive contract across clusters (core/module/industry/
     * location/usecase): viewport meta, single H1, sticky mobile CTA with
     * safe-area padding, horizontally scrolling workflow (no page overflow).
     */
    public function test_sample_pages_carry_responsive_contract(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoPageGenerator::generate(1, true, 'pillars');
        SeoPageGenerator::generate(1, true, 'modules', 3);
        SeoPageGenerator::generate(1, true, 'industries', 2);
        SeoPageGenerator::generate(1, true, 'locations', 2);
        SeoPageGenerator::generate(1, true, 'solutions', 2);

        $pages = SeoPage::indexable()->limit(12)->get();
        $this->assertGreaterThanOrEqual(5, $pages->count());

        foreach ($pages as $page) {
            $response = $this->get('/'.$page->path);
            $response->assertOk();
            $html = $response->getContent();
            $this->assertStringContainsString('name="viewport"', $html, $page->path);
            $this->assertEquals(1, substr_count($html, '<h1'), $page->path);
            $this->assertStringContainsString('env(safe-area-inset-bottom)', $html, $page->path);
            $this->assertStringContainsString('overflow-x-auto', $html, $page->path);
        }
    }
}
