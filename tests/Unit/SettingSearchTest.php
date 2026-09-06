<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingSearchTest extends TestCase
{
    public function test_searchable_metadata_contains_human_label_and_description(): void
    {
        $meta = SettingCatalog::get('general.default_currency');
        $search = strtolower($meta['label'].' '.$meta['description'].' '.$meta['key']);
        $this->assertStringContainsString('mata uang default', $search);
        $this->assertStringContainsString('general.default_currency', $search);
    }
}
