<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSitemapIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_index_lists_children(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<sitemapindex', false)
            ->assertSee('/sitemaps/core.xml', false);
    }
}
