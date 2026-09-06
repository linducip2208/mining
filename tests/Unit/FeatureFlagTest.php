<?php

namespace Tests\Unit;

use App\Support\FeatureFlag;
use PHPUnit\Framework\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_feature_flag_maps_sensitive_routes_to_modules(): void
    {
        $this->assertSame('fuel', FeatureFlag::moduleForRoute('fuel.dashboard'));
        $this->assertSame('hse', FeatureFlag::moduleForRoute('hse.reports.index'));
        $this->assertNull(FeatureFlag::moduleForRoute('setting.index'));
    }
}
