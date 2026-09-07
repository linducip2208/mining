<?php

namespace Tests\Feature\Seo;

use App\Services\SeoQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoOrphanTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_orphans_after_tier_generation(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $this->assertEquals(0, SeoQualityService::orphanCount());
    }
}
