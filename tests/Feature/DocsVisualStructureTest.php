<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use Tests\TestCase;

/**
 * Struktur visual portal docs: homepage, halaman, dan konsistensi komponen.
 */
class DocsVisualStructureTest extends TestCase
{
    public function test_homepage_has_hero_search_quick_actions_modules_and_flows(): void
    {
        $resp = $this->get('/docs');
        $resp->assertStatus(200);
        $html = $resp->getContent();
        $this->assertStringContainsString('Mining ERP Documentation', $html);
        $this->assertStringContainsString('Cari fitur, tutorial, menu, error', $html);
        foreach (['Mulai dari Sini', 'Mine to Cash', 'Procure to Pay', 'Payroll to Accounting', 'Maintenance Flow', 'FAQ'] as $qa) {
            $this->assertStringContainsString($qa, $html);
        }
        foreach (['Manajemen BBM', 'Dispatch &amp; Hauling', 'Alur Kerja End-to-End'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_module_cards_show_counts_and_popular_pages(): void
    {
        $html = $this->get('/docs')->getContent();
        $this->assertStringContainsString('Tutorial ·', $html);
        $this->assertStringContainsString('Screenshot', $html);
        $this->assertStringContainsString('Populer', $html);
        $this->assertMatchesRegularExpression('/\d+ Tutorial/', $html);
    }

    public function test_doc_page_has_breadcrumb_meta_toc_and_actions(): void
    {
        $resp = $this->get('/docs/fuel/issues');
        $resp->assertStatus(200);
        $html = $resp->getContent();
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertStringContainsString('Peran', $html);
        $this->assertStringContainsString('Tingkat', $html);
        $this->assertStringContainsString('Di halaman ini', $html);
        foreach (['Salin Tautan', 'Buka di Aplikasi', 'Laporkan Masalah Docs', 'Tutorial Terkait'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('rel="prev"', $html);
        $this->assertStringContainsString('rel="next"', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_health_page_requires_superadmin(): void
    {
        $this->get('/docs/health')->assertStatus(403);
    }
}
