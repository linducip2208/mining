<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class BrandingSettingTest extends TestCase
{
    public function test_white_label_catalog_is_complete(): void
    {
        foreach (['branding.app_name', 'branding.logo_main', 'branding.favicon', 'branding.primary_color', 'branding.powered_by_url'] as $key) {
            $this->assertSame('Branding', SettingCatalog::get($key)['group']);
        }
    }
}
