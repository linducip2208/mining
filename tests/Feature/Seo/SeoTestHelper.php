<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoContentService;
use App\Services\SeoPageGenerator;
use App\Services\SeoQualityService;
use Database\Seeders\SeoSeeder;

/**
 * Shared builders for pSEO tests. Pages are composed through the real
 * content service so tests exercise production code paths.
 */
final class SeoTestHelper
{
    public static function seedCatalog(object $test): void
    {
        $test->seed(SeoSeeder::class);
    }

    public static function generatePillars(): void
    {
        SeoPageGenerator::generate(1, true, 'pillars');
    }

    public static function makePage(array $overrides = []): SeoPage
    {
        $keyword = $overrides['keyword'] ?? 'erp mining test '.uniqid();
        $content = SeoContentService::compose($keyword, 'SOFTWARE');
        $page = SeoPage::create(array_merge([
            'fingerprint' => hash('sha256', uniqid('fp', true)),
            'path' => 'test/'.uniqid(),
            'intent' => 'SOFTWARE',
            'cluster' => 'core',
            'keyword' => $keyword,
            'title' => ucwords($keyword).' | Source Code Mulai Rp12 Juta | Mining ERP',
            'h1' => ucwords($keyword),
            'description' => ucwords($keyword).' terintegrasi. Source code mulai Rp12 juta.',
            'canonical' => url('/test/'.uniqid()),
            'commercial_score' => 80,
            'content' => $content,
            'content_hash' => hash('sha256', json_encode($content)),
            'status' => 'PUBLISHED',
            'indexable' => true,
            'published_at' => now(),
            'generated_at' => now(),
        ], $overrides));
        SeoQualityService::audit($page, true);

        return $page->fresh();
    }
}
