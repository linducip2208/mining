<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_unique_across_indexable_pages(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $pages = SeoPage::indexable()->get();
        $this->assertGreaterThan(0, $pages->count());
        $this->assertEquals($pages->count(), $pages->pluck('title')->unique()->count());
        $this->assertEquals($pages->count(), $pages->pluck('h1')->unique()->count());
        $this->assertEquals($pages->count(), $pages->pluck('description')->unique()->count());
    }

    public function test_opengraph_rendered(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $this->get('/erp-tambang')->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('name="twitter:card"', false);
    }
}
