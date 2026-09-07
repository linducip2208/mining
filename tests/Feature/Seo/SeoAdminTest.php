<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed(\Database\Seeders\CoreSeeder::class);
        SeoTestHelper::seedCatalog($this);

        return User::where('username', 'superadmin')->firstOrFail();
    }

    public function test_dashboard_pages_catalog_require_permission(): void
    {
        $this->get('/marketing/seo')->assertRedirect('/login');
    }

    public function test_admin_screens_render(): void
    {
        $this->actingAs($this->admin());
        SeoTestHelper::generatePillars();

        $this->get('/marketing/seo')->assertOk()->assertSee('SEO Dashboard', false);
        $this->get('/marketing/seo/pages')->assertOk();
        $this->get('/marketing/seo/catalog')->assertOk();

        $page = SeoPage::firstOrFail();
        $this->get("/marketing/seo/pages/{$page->id}")->assertOk();
    }

    public function test_publish_noindex_archive_flow(): void
    {
        $this->actingAs($this->admin());
        $page = SeoTestHelper::makePage(['status' => 'DRAFT', 'indexable' => false]);

        $this->post("/marketing/seo/pages/{$page->id}/publish")->assertRedirect();
        $this->assertEquals('PUBLISHED', $page->fresh()->status);

        $this->post("/marketing/seo/pages/{$page->id}/noindex", ['reason' => 'test'])->assertRedirect();
        $this->assertEquals('NOINDEX', $page->fresh()->status);

        $this->post("/marketing/seo/pages/{$page->id}/archive", ['redirect_to' => '/erp-mining'])->assertRedirect();
        $this->assertEquals('ARCHIVED', $page->fresh()->status);

        $this->get('/' . $page->path)->assertRedirect('/erp-mining');
    }
}
