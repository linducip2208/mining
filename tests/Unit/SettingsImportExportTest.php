<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingsImportExportTest extends TestCase
{
    public function test_sensitive_settings_are_known_before_import_export(): void
    {
        $this->assertTrue(SettingCatalog::get('email.smtp_password')['sensitive']);
        $this->assertFalse(SettingCatalog::get('branding.app_name')['sensitive']);
    }
}
