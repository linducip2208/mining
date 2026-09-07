<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoNoindexTest extends TestCase
{
    use RefreshDatabase;

    public function test_failing_page_renders_noindex(): void
    {
        SeoTestHelper::seedCatalog($this);
        $page = SeoTestHelper::makePage(['status' => 'NOINDEX', 'indexable' => false, 'noindex_reason' => 'manual review']);

        $this->get('/'.$page->path)->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_indexable_page_renders_index(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $page = SeoPage::indexable()->firstOrFail();
        $this->get('/'.$page->path)->assertOk()
            ->assertSee('<meta name="robots" content="index,follow">', false);
    }

    public function test_draft_page_404(): void
    {
        SeoTestHelper::seedCatalog($this);
        $page = SeoTestHelper::makePage(['status' => 'DRAFT', 'indexable' => false]);

        $this->get('/'.$page->path)->assertNotFound();
    }
}
