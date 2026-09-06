<?php

namespace App\Support;

use App\Models\Setting;

final class FeatureFlag
{
    private const ROUTE_MODULES = [
        'fleet' => ['fleet.', 'equipment.', 'vehicles.', 'equipment-categories.'],
        'fuel' => ['fuel.', 'fuel-', 'fuel_'],
        'hse' => ['hse.', 'compliance.'],
        'quality' => ['quality-', 'specs.', 'samples.'],
        'dispatch' => ['dispatch.', 'loading-points.', 'dumping-points.', 'hauling-routes.'],
        'ai' => ['ai.', 'forecast.'],
        'telematics' => ['telematics.', 'weighbridge.devices.'],
    ];

    public static function enabled(string $module): bool
    {
        return filter_var(Setting::get('modules.'.$module.'_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public static function moduleForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }
        foreach (self::ROUTE_MODULES as $module => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($routeName, $prefix)) {
                    return $module;
                }
            }
        }

        return null;
    }
}
