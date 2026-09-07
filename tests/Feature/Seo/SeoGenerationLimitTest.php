<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoPageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoGenerationLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_cap_respected(): void
    {
        SeoTestHelper::seedCatalog($this);

        SeoPageGenerator::generate(1, true);

        $this->assertLessThanOrEqual(SeoPageGenerator::TIERS[1], SeoPage::where('status', 'PUBLISHED')->count());
        $this->assertLessThanOrEqual(SeoPageGenerator::MAX_INDEXABLE_PAGES, SeoPage::indexable()->count());
    }

    public function test_capacity_never_exceeds_max(): void
    {
        $this->assertEquals(22000, SeoPageGenerator::MAX_INDEXABLE_PAGES);
        $this->assertLessThanOrEqual(SeoPageGenerator::MAX_INDEXABLE_PAGES, SeoPageGenerator::dryRun()['total']);
    }
}
