<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingLabelTest extends TestCase
{
    public function test_setting_labels_are_human_readable(): void
    {
        $this->assertSame('Tarif PPh 21', SettingCatalog::get('payroll.pph21_rate')['label']);
        $this->assertSame('Mata Uang Default', SettingCatalog::get('finance.default_currency')['label']);
        $this->assertSame('Batas Stok Minimum', SettingCatalog::get('inventory.low_stock_threshold')['label']);
    }
}
