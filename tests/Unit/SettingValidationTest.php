<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingValidationTest extends TestCase
{
    public function test_typed_settings_have_matching_validation(): void
    {
        $this->assertStringContainsString('max:100', (string) SettingCatalog::get('payroll.pph21_rate')['validation']);
        $this->assertSame('boolean', SettingCatalog::get('finance.lock_posted_journal')['validation']);
        $this->assertSame('date_format:H:i', SettingCatalog::get('mining.production_cutoff_time')['validation']);
    }
}
