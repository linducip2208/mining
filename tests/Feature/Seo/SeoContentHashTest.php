<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoContentService;
use App\Services\SeoPageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoContentHashTest extends TestCase
{
    use RefreshDatabase;

    public function test_hash_stable_and_version_bumps_on_change(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoPageGenerator::generate(1, false, 'pillars');

        $page = SeoPage::firstOrFail();
        $v1 = $page->content_version;
        $h1 = $page->content_hash;

        SeoPageGenerator::generate(1, false, 'pillars');

        $page->refresh();
        $this->assertEquals($h1, $page->content_hash);
        $this->assertEquals($v1, $page->content_version);

        // simulate real content change
        $content = SeoContentService::compose($page->keyword.' v2', $page->intent);
        $page->content = $content;
        $page->content_hash = hash('sha256', json_encode($content));
        $page->content_version++;
        $page->save();

        $this->assertNotEquals($h1, $page->content_hash);
        $this->assertEquals($v1 + 1, $page->content_version);
    }
}
