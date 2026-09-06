<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class ModelSelectSettingTest extends TestCase
{
    public function test_model_select_has_server_side_reference_metadata(): void
    {
        $meta = SettingCatalog::get('inventory.default_warehouse_id');
        $this->assertSame('model_select', $meta['type']);
        $this->assertSame('warehouse', $meta['model']);
        $this->assertSame('name', $meta['label_column']);
    }
}
