<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoInternalLinkService;
use App\Services\SeoSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_required_types_without_fake_ratings(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $page = SeoPage::where('path', 'source-code-erp-mining')->firstOrFail();
        $schema = SeoSchemaService::forPage($page, $page->content, SeoInternalLinkService::breadcrumbs($page));
        $types = collect($schema['@graph'])->pluck('@type')->all();

        foreach (['Organization', 'WebPage', 'SoftwareApplication', 'BreadcrumbList', 'FAQPage'] as $expected) {
            $this->assertContains($expected, $types);
        }
        $this->assertStringNotContainsString('AggregateRating', json_encode($schema));
        $this->assertStringNotContainsString('Review', json_encode($schema));
    }

    public function test_offer_price_is_starting_price_idr(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $page = SeoPage::where('path', 'harga-erp-tambang')->firstOrFail();
        $schema = SeoSchemaService::forPage($page, $page->content, []);
        $offer = collect($schema['@graph'])->firstWhere('@type', 'SoftwareApplication')['offers'];

        $this->assertEquals('12000000', $offer['price']);
        $this->assertEquals('IDR', $offer['priceCurrency']);
        $this->assertStringContainsString('starting price', strtolower($offer['description']));
    }
}
