<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 0 tautan rusak di portal docs: related, prev/next, homepage, sidebar, route mapping.
 */
class DocsLinkTest extends TestCase
{
    public function test_all_related_links_resolve(): void
    {
        $bad = [];
        foreach (DocRegistry::allPages() as $p) {
            foreach ($p['related'] ?? [] as [$label, $url]) {
                if (!str_starts_with($url, '/docs/')) {
                    continue;
                }
                $parts = explode('/', trim($url, '/'));
                if (($parts[0] ?? '') !== 'docs' || !DocRegistry::page($parts[1] ?? '', $parts[2] ?? '')) {
                    $bad[] = "{$p['url']} → {$url}";
                }
            }
        }
        $this->assertEmpty($bad, 'Tautan terkait rusak: ' . implode(', ', $bad));
    }

    public function test_homepage_cards_and_flows_point_to_live_pages(): void
    {
        $html = $this->get('/docs')->getContent();
        preg_match_all('/href="(\/docs\/[^"]+)"/', $html, $m);
        $urls = array_unique($m[1]);
        $this->assertNotEmpty($urls);
        $dead = [];
        foreach ($urls as $u) {
            if (str_ends_with($u, '.xml')) {
                continue;
            }
            $code = $this->get($u)->status();
            if (!in_array($code, [200, 302])) {
                $dead[] = "{$u} => {$code}";
            }
        }
        $this->assertEmpty($dead, 'Tautan homepage mati: ' . implode(', ', $dead));
    }

    public function test_route_mapping_points_to_existing_routes_and_docs(): void
    {
        $bad = [];
        foreach (DocRegistry::routeDocMap() as $route => $url) {
            if (!Route::has($route)) {
                $bad[] = "route hilang: {$route}";
                continue;
            }
            $parts = explode('/', trim($url, '/'));
            if (!DocRegistry::page($parts[1] ?? '', $parts[2] ?? '')) {
                $bad[] = "docs hilang: {$url}";
            }
        }
        $this->assertEmpty($bad, 'Mapping rusak: ' . implode(', ', $bad));
    }

    public function test_no_hash_links_anywhere_in_docs(): void
    {
        $checked = 0;
        foreach (array_merge(['/docs'], array_map(fn ($p) => $p['url'], DocRegistry::allPages())) as $url) {
            $html = $this->get($url)->getContent();
            $this->assertStringNotContainsString('href="#"', $html, "Hash link di {$url}");
            $checked++;
        }
        $this->assertGreaterThan(100, $checked);
    }
}
