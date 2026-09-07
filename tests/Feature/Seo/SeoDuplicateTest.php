<?php

namespace Tests\Feature\Seo;

use App\Models\SeoPage;
use App\Services\SeoQualityService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_title_fails_gate_and_demotes(): void
    {
        SeoTestHelper::seedCatalog($this);
        $original = SeoTestHelper::makePage();
        $dupe = SeoTestHelper::makePage(['title' => $original->title]);

        $result = SeoQualityService::audit($dupe);

        $this->assertEquals('FAIL', $result['verdict']);
        $this->assertFalse($dupe->fresh()->indexable);
    }

    public function test_fingerprint_unique_constraint(): void
    {
        SeoTestHelper::seedCatalog($this);
        $page = SeoTestHelper::makePage();

        $this->expectException(QueryException::class);
        SeoPage::create($page->toArray());
    }
}
