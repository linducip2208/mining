<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoPageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoGeneratorIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_double_run_creates_no_duplicates(): void
    {
        SeoTestHelper::seedCatalog($this);

        SeoPageGenerator::generate(1, true, 'pillars');
        $first = SeoPage::count();

        SeoPageGenerator::generate(1, true, 'pillars');

        $this->assertEquals($first, SeoPage::count());
        $this->assertEquals(SeoPage::count(), SeoPage::distinct()->count('fingerprint'));
    }
}
