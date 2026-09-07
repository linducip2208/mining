<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoInternalLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInternalLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_capped_and_relevant(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $page = SeoPage::indexable()->firstOrFail();
        $related = SeoInternalLinkService::related($page);

        $this->assertLessThanOrEqual(SeoInternalLinkService::MAX_LINKS, count($related));
        foreach ($related as $link) {
            $this->assertArrayHasKey('url', $link);
            $this->assertNotEquals($page->url(), $link['url']);
        }
    }

    public function test_no_self_links(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        foreach (SeoPage::indexable()->limit(20)->get() as $page) {
            foreach (SeoInternalLinkService::related($page) as $link) {
                $linkedPath = ltrim((string) parse_url($link['url'], PHP_URL_PATH), '/');
                $this->assertNotEquals($page->path, $linkedPath);
            }
        }
        $this->assertTrue(true);
    }
}
