<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoInternalLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoBreadcrumbTest extends TestCase
{
    use RefreshDatabase;

    public function test_breadcrumb_visible_and_in_schema(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $page = SeoPage::where('path', 'like', 'modul/%')->first();
        if (! $page) {
            SeoTestHelper::makePage(['path' => 'modul/t-'.uniqid(), 'keyword' => 'uji breadcrumb '.uniqid()]);
            $page = SeoPage::orderByDesc('id')->first();
        }

        $crumbs = SeoInternalLinkService::breadcrumbs($page);
        $this->assertEquals('Beranda', $crumbs[0]['name']);
        $this->assertEquals($page->h1, end($crumbs)['name']);

        $this->get('/'.$page->path)->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('BreadcrumbList', false);
    }
}
