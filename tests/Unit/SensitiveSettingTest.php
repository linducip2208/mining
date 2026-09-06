<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SensitiveSettingTest extends TestCase
{
    public function test_secret_fields_are_typed_and_sensitive(): void
    {
        $meta = SettingCatalog::get('email.smtp_password');
        $this->assertSame('secret', $meta['type']);
        $this->assertTrue($meta['sensitive']);
    }
}
