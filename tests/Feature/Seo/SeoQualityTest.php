<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_pages_meet_thresholds(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        foreach (SeoPage::indexable()->get() as $page) {
            $this->assertGreaterThanOrEqual(SeoQualityService::QUALITY_THRESHOLD, $page->quality_score, $page->path);
            $this->assertGreaterThanOrEqual(SeoQualityService::UNIQUENESS_THRESHOLD, $page->uniqueness_score, $page->path);
        }
    }

    public function test_thin_page_fails_gate(): void
    {
        SeoTestHelper::seedCatalog($this);
        $page = SeoTestHelper::makePage(['content' => ['faqs' => [], 'features' => [], 'workflow' => []]]);

        $result = SeoQualityService::check($page->fresh());

        $this->assertEquals('FAIL', $result['verdict']);
    }
}
