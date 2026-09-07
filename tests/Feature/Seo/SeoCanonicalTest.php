<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoCanonicalTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_matches_page_url(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        foreach (SeoPage::all() as $page) {
            $this->assertEquals($page->url(), $page->canonical);
        }
    }

    public function test_canonical_tag_rendered(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $page = SeoPage::where('path', 'erp-mining')->firstOrFail();
        $this->get('/erp-mining')->assertOk()
            ->assertSee('<link rel="canonical" href="'.e($page->url()).'">', false);
    }
}
