<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use Tests\TestCase;

/**
 * Gambar docs: referensi valid, lazy-load, dan alt terisi.
 */
class DocsImageTest extends TestCase
{
    public function test_no_missing_screenshot_reference(): void
    {
        $missing = [];
        foreach (DocRegistry::allPages() as $p) {
            if (empty($p['shot'])) {
                continue;
            }
            if (!file_exists(public_path('docs-assets/screenshots/' . ltrim($p['shot'], '/')))) {
                $missing[] = "{$p['url']} => {$p['shot']}";
            }
        }
        $this->assertEmpty($missing, 'Screenshot hilang: ' . implode(', ', $missing));
    }

    public function test_screenshots_lazy_with_alt(): void
    {
        foreach (['/docs/fuel/issues', '/docs/dispatch/board', '/docs/hse/dashboard'] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertStringContainsString('loading="lazy"', $html, "Lazy hilang di {$url}");
            $this->assertMatchesRegularExpression('/<img[^>]+alt="[^"]+"/', $html, "Alt hilang di {$url}");
        }
    }

    public function test_lightbox_markup_present_on_shot_pages(): void
    {
        $html = $this->get('/docs/fuel/dashboard')->getContent();
        $this->assertStringContainsString('docsLightbox', $html);
        $this->assertStringContainsString('data-lb="full"', $html);
        $this->assertStringContainsString('aria-label="Penampil screenshot"', $html);
    }
}
