<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use Tests\TestCase;

/**
 * Search docs: suggest JSON, kategori, highlight, dan halaman hasil.
 */
class DocsSearchTest extends TestCase
{
    public function test_suggest_returns_shaped_json(): void
    {
        $resp = $this->getJson('/docs/suggest?q=timbangan');
        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertNotEmpty($data);
        $this->assertLessThanOrEqual(8, count($data));
        foreach ($data as $row) {
            $this->assertArrayHasKey('title', $row);
            $this->assertArrayHasKey('url', $row);
            $this->assertArrayHasKey('module', $row);
            $this->assertArrayHasKey('category', $row);
            $this->assertContains($row['category'], ['Tutorial', 'Module', 'Workflow', 'FAQ', 'Troubleshooting']);
        }
    }

    public function test_suggest_requires_two_chars(): void
    {
        $this->getJson('/docs/suggest?q=x')->assertJson(['data' => []]);
    }

    public function test_search_page_groups_and_highlights(): void
    {
        $html = $this->get('/docs/search?q=' . urlencode('fuel issue'))->getContent();
        $this->assertStringContainsString('<mark', $html);
        $this->assertMatchesRegularExpression('/Tutorial|Workflow|FAQ|Troubleshooting/', $html);
    }

    public function test_search_categories_cover_spec(): void
    {
        $this->assertEquals('FAQ', DocRegistry::categoryFor('faq'));
        $this->assertEquals('Troubleshooting', DocRegistry::categoryFor('troubleshooting'));
        $this->assertEquals('Workflow', DocRegistry::categoryFor('workflows'));
        $this->assertEquals('Tutorial', DocRegistry::categoryFor('fuel'));
    }
}
