<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class BrandingUploadTest extends TestCase
{
    public function test_branding_assets_reject_svg_by_metadata(): void
    {
        $this->assertStringContainsString('mimes:png,jpg,jpeg,webp,ico', SettingCatalog::get('branding.logo_main')['validation']);
    }
}
