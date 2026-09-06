<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingAuditTest extends TestCase
{
    public function test_secret_metadata_is_marked_for_redaction(): void
    {
        foreach (['email.smtp_password', 'notification.whatsapp_webhook_url'] as $key) {
            $this->assertTrue(SettingCatalog::get($key)['sensitive']);
        }
    }
}
