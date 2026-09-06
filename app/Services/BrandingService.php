<?php

namespace App\Services;

use App\Models\Setting;

final class BrandingService
{
    public static function appName(): string
    {
        return (string) Setting::get('branding.app_name', config('app.name', 'Mining ERP Pro'));
    }

    public static function companyName(): string
    {
        return (string) Setting::get('system.company_name', 'PT Tambang Sejahtera');
    }

    public static function tagline(): string
    {
        return (string) Setting::get('branding.tagline', 'Integrated Mining ERP');
    }

    public static function copyright(): string
    {
        return '© '.now()->year.' '.self::companyName();
    }
}
