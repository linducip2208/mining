<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoLocationClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_fake_office_claims_anywhere(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        foreach (SeoPage::all() as $page) {
            $haystack = strtolower($page->title.' '.$page->h1.' '.$page->description.' '.json_encode($page->content));
            foreach (['kantor kami di', 'tim kami di', 'cabang kami di'] as $bad) {
                $this->assertStringNotContainsString($bad, $haystack, $page->path);
            }
        }
    }

    public function test_location_wording_uses_for_companies_in(): void
    {
        SeoTestHelper::seedCatalog($this);
        \App\Services\SeoPageGenerator::generate(1, true, 'locations', 5);

        $located = SeoPage::indexable()->whereNotNull('location_id')->first();
        $this->assertNotNull($located);
        $this->get('/'.$located->path)->assertOk()
            ->assertSee('untuk Perusahaan di '.$located->location->name, false);
    }
}
