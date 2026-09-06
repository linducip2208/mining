<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class BrandingFallbackTest extends TestCase
{
    public function test_catalog_fallback_keeps_a_human_label(): void
    {
        $meta = SettingCatalog::get('some.future_branding_key');
        $this->assertNotSame('some.future_branding_key', $meta['label']);
    }
}
