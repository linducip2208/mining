<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use Tests\TestCase;

class DocsScreenshotTest extends TestCase
{
    public function test_every_referenced_screenshot_exists(): void
    {
        $missing = [];
        $checked = 0;
        foreach (DocRegistry::allPages() as $p) {
            if (empty($p['shot'])) {
                continue;
            }
            $checked++;
            $path = public_path('docs-assets/screenshots/' . ltrim($p['shot'], '/'));
            if (!file_exists($path)) {
                $missing[] = "{$p['url']} => {$p['shot']}";
                continue;
            }
            $this->assertGreaterThan(1024, filesize($path), "Screenshot terlalu kecil/rusak: {$p['shot']}");
        }
        $this->assertGreaterThanOrEqual(50, $checked, 'Cakupan screenshot menyusut.');
        $this->assertEmpty($missing, 'Screenshot hilang: ' . implode(', ', $missing));
    }

    public function test_no_broken_images_on_docs_pages(): void
    {
        foreach (DocRegistry::allPages() as $p) {
            if (empty($p['shot'])) {
                continue;
            }
            $path = public_path('docs-assets/screenshots/' . ltrim($p['shot'], '/'));
            if (!file_exists($path)) {
                continue; // sudah dilaporkan test di atas
            }
            $info = @getimagesize($path);
            $this->assertNotFalse($info, "Bukan gambar valid: {$p['shot']}");
            $this->assertGreaterThanOrEqual(1000, $info[0], "Lebar screenshot mencurigakan: {$p['shot']}");
        }
    }
}
