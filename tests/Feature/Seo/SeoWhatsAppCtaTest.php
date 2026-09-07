<?php

namespace Tests\Feature\Seo;

use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoWhatsAppCtaTest extends TestCase
{
    use RefreshDatabase;

    public function test_number_normalized_to_international(): void
    {
        $this->assertEquals('6281296052010', WhatsappService::normalize('081296052010'));
        $this->assertEquals('6281296052010', WhatsappService::normalize('+62 812-9605-2010'));
        $this->assertEquals('6281296052010', WhatsappService::normalize('6281296052010'));
    }

    public function test_link_contains_encoded_message_and_attribution(): void
    {
        $url = WhatsappService::link('Halo test', ['page' => 'erp-mining', 'cluster' => 'core', 'intent' => 'SOFTWARE']);

        $this->assertStringStartsWith('https://wa.me/6281296052010?text=', $url);
        $this->assertStringContainsString(rawurlencode('Halo test'), $url);
        $this->assertStringContainsString('erp-mining', $url);
    }

    public function test_click_tracking_stores_no_pii(): void
    {
        SeoTestHelper::seedCatalog($this);
        $page = SeoTestHelper::makePage();

        $this->postJson('/seo/cta-click', [
            'seo_page_id' => $page->id, 'cta_position' => 'hero', 'cta_type' => 'whatsapp',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('seo_cta_clicks', [
            'seo_page_id' => $page->id, 'cta_position' => 'hero', 'cta_type' => 'whatsapp',
        ]);
    }
}
