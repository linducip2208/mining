<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_paths_are_clean_without_query_strings(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        foreach (SeoPage::pluck('path') as $path) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_\/]+$/', $path);
            $this->assertStringNotContainsString('?', $path);
            $this->assertStringNotContainsString('seo/page', $path);
        }
    }

    public function test_paths_unique_per_intent_combination(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $this->assertEquals(
            SeoPage::count(),
            SeoPage::distinct()->count('path')
        );
    }
}
