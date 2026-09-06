<?php

namespace Tests\Unit;

use App\Support\SettingCatalog;
use PHPUnit\Framework\TestCase;

class SettingResetTest extends TestCase
{
    public function test_every_catalog_entry_has_a_default_value(): void
    {
        foreach (SettingCatalog::all() as $meta) {
            $this->assertArrayHasKey('default', $meta);
            $this->assertArrayHasKey('default_value', $meta);
        }
    }
}
