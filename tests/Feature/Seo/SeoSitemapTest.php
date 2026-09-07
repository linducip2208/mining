<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoSitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_sitemap_contains_only_indexable_with_lastmod(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $response = $this->get('/sitemaps/core.xml');
        $response->assertOk()->assertHeader('Content-Type', 'application/xml');

        $indexable = SeoPage::indexable()->where('cluster', 'core')->count();
        $this->assertGreaterThan(0, $indexable);
        $this->assertEquals($indexable, substr_count($response->getContent(), '<url>'));
        $this->assertStringContainsString('<lastmod>', $response->getContent());
    }

    public function test_unknown_sitemap_group_404(): void
    {
        SeoTestHelper::seedCatalog($this);

        $this->get('/sitemaps/nope.xml')->assertNotFound();
    }

    public function test_service_child_returns_null_for_empty_cluster(): void
    {
        SeoTestHelper::seedCatalog($this);

        $this->assertNull(SeoSitemapService::child('module'));
    }
}
