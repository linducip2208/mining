<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_is_exactly_12_juta_everywhere(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $this->assertEquals(12000000, WhatsappService::PRICE_AMOUNT);
        $this->assertEquals('Rp12.000.000', WhatsappService::priceFormatted());
        $this->assertEquals('Rp12 Juta', WhatsappService::priceShort());

        foreach (SeoPage::indexable()->get() as $page) {
            $content = $page->content ?? [];
            $this->assertEquals(12000000, $content['price']['amount'] ?? null, $page->path);
            $this->assertEquals('Rp12.000.000', $content['price']['display'] ?? null, $page->path);
        }
    }

    public function test_price_note_clarifies_starting_price(): void
    {
        SeoTestHelper::seedCatalog($this);
        SeoTestHelper::generatePillars();

        $this->get('/harga-erp-tambang')->assertOk()
            ->assertSee('Harga merupakan harga mulai', false);
    }
}
