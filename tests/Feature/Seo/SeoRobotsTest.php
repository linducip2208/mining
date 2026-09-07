<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_allows_marketing_blocks_private(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString('Allow: /', $body);
        $this->assertStringContainsString('Sitemap:', $body);
        $this->assertStringContainsString('/sitemap.xml', $body);
        foreach (['/admin', '/login', '/cari-solusi'] as $blocked) {
            $this->assertStringContainsString("Disallow: {$blocked}", $body);
        }
    }
}
