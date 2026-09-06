<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_docs_index_ok(): void
    {
        $this->get('/docs')->assertStatus(200);
    }

    public function test_every_section_redirects_to_first_page(): void
    {
        foreach (DocRegistry::sections() as $slug => $s) {
            $resp = $this->get("/docs/{$slug}");
            $resp->assertRedirect("/docs/{$slug}/" . array_key_first($s['pages']));
        }
    }

    public function test_every_docs_page_ok(): void
    {
        $checked = 0;
        foreach (DocRegistry::allPages() as $p) {
            $this->get($p['url'])->assertStatus(200);
            $checked++;
        }
        $this->assertGreaterThanOrEqual(50, $checked, 'Cakupan halaman docs menyusut.');
    }

    public function test_docs_search_ok(): void
    {
        $this->get('/docs/search?q=timbangan')->assertStatus(200);
        $this->get('/docs/search?q=jurnal')->assertStatus(200);
    }

    public function test_docs_sitemap_ok(): void
    {
        $resp = $this->get('/docs/sitemap.xml');
        $resp->assertStatus(200);
        $this->assertStringContainsString('<urlset', $resp->getContent());
    }

    public function test_docs_unknown_page_404(): void
    {
        $this->get('/docs/nope/nothing')->assertStatus(404);
    }

    public function test_docs_require_login_when_private(): void
    {
        // Setting docs.public=false mengunci portal di balik login
        \App\Models\Setting::updateOrCreate(['key' => 'docs.public'], ['value' => 'false', 'type' => 'bool']);
        $this->get('/docs')->assertRedirect('/login');

        \App\Models\Setting::where('key', 'docs.public')->update(['value' => 'true']);
        $this->get('/docs')->assertStatus(200);
    }
}
