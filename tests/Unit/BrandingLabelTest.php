<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class BrandingLabelTest extends TestCase
{
    public function test_branding_settings_are_catalogued(): void
    {
        foreach (['branding.app_name', 'branding.tagline', 'branding.primary_color', 'system.company_name'] as $key) {
            $meta = SettingCatalog::get($key);
            $this->assertNotSame($key, $meta['label']);
            $this->assertNotEmpty($meta['description']);
        }
    }
}
