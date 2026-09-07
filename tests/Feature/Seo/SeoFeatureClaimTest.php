<?php

namespace Tests\Feature\Seo;

use App\Models\SeoFeature;
use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoFeatureClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_claimed_features_implemented_and_enabled(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        foreach (SeoPage::indexable()->get() as $page) {
            foreach ($page->content['features'] ?? [] as $f) {
                $feature = SeoFeature::where('slug', $f['slug'])->first();
                $this->assertNotNull($feature, "unknown feature {$f['slug']} on {$page->path}");
                $this->assertTrue((bool) $feature->implemented, $f['slug']);
                $this->assertTrue((bool) $feature->marketing_enabled, $f['slug']);
            }
        }
    }
}
