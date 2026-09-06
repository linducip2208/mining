<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingCatalogTest extends TestCase
{
    public function test_production_settings_have_human_metadata(): void
    {
        foreach (['payroll.pph21_rate', 'payroll.bpjs_health_employee_rate', 'finance.default_currency', 'sales.invoice_prefix', 'inventory.low_stock_threshold'] as $key) {
            $meta = SettingCatalog::get($key);
            $this->assertNotSame($key, $meta['label']);
            $this->assertNotEmpty($meta['description']);
            $this->assertNotEmpty($meta['group']);
            $this->assertArrayHasKey('type', $meta);
            $this->assertArrayHasKey('default', $meta);
        }
    }
}
