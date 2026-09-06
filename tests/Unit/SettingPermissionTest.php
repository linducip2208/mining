<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingPermissionTest extends TestCase
{
    public function test_sensitive_and_advanced_controls_are_classified(): void
    {
        $this->assertTrue(SettingCatalog::get('email.smtp_password')['sensitive']);
        $this->assertSame('Advanced', SettingCatalog::get('developer_labels_enabled')['group']);
        $this->assertSame('Keamanan', SettingCatalog::get('security.mfa_enabled')['group']);
    }
}
