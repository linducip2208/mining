<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use Tests\TestCase;

class DocsPageTest extends TestCase
{
    public function test_every_page_has_required_content_blocks(): void
    {
        $checked = 0;
        foreach (DocRegistry::allPages() as $p) {
            $this->assertNotEmpty($p['title'] ?? null, "Judul kosong: {$p['url']}");
            $this->assertNotEmpty($p['purpose'] ?? $p['faqs'] ?? null, "Deskripsi/FAQ kosong: {$p['url']}");
            $this->assertNotEmpty($p['module'] ?? null, "Modul kosong: {$p['url']}");
            $checked++;
        }
        $this->assertGreaterThanOrEqual(50, $checked);
    }

    public function test_every_page_renders_title_and_no_hash_links(): void
    {
        foreach (DocRegistry::allPages() as $p) {
            $resp = $this->get($p['url']);
            $resp->assertStatus(200);
            $resp->assertSee($p['title']);
            $this->assertStringNotContainsString('href="#"', $resp->getContent(), "Hash link di {$p['url']}");
        }
    }

    public function test_related_links_resolve(): void
    {
        $bad = [];
        foreach (DocRegistry::allPages() as $p) {
            foreach ($p['related'] ?? [] as [$label, $url]) {
                if (!str_starts_with($url, '/docs/')) {
                    continue;
                }
                $parts = explode('/', trim($url, '/'));
                // /docs/{section} atau /docs/{section}/{page}
                if (count($parts) === 2) {
                    if (!isset(DocRegistry::sections()[$parts[1]])) {
                        $bad[] = "{$p['url']} -> {$url}";
                    }
                } elseif (count($parts) === 3) {
                    if (!DocRegistry::page($parts[1], $parts[2])) {
                        $bad[] = "{$p['url']} -> {$url}";
                    }
                }
            }
        }
        $this->assertEmpty($bad, 'Related link rusak: ' . implode(', ', $bad));
    }

    public function test_search_finds_expected_keywords(): void
    {
        foreach (['timbangan' => 'weighbridge', 'invoice' => 'sales', 'jurnal' => 'finance', 'cuti' => 'hr', 'stok' => 'inventory'] as $q => $mustContain) {
            $hits = DocRegistry::search($q);
            $this->assertNotEmpty($hits, "Pencarian '{$q}' tanpa hasil");
            $urls = implode(' ', array_column(array_column($hits, 'page'), 'url'));
            $this->assertStringContainsString($mustContain, $urls, "Pencarian '{$q}' tak relevan");
        }
    }
}
