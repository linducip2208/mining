<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

final class BrandingService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public static function appName(): string
    {
        return (string) self::get('branding.app_name', config('app.name', 'Mining ERP Pro'));
    }

    public static function shortName(): string
    {
        return (string) self::get('branding.app_short_name', self::appName());
    }

    public static function companyName(): string
    {
        return (string) self::get('system.company_name', 'PT Tambang Sejahtera');
    }

    public static function tagline(): string
    {
        return (string) self::get('branding.tagline', 'Integrated Mining ERP');
    }

    public static function copyright(): string
    {
        return (string) self::get('branding.copyright_text', '© '.now()->year.' '.self::companyName());
    }

    public static function poweredBy(): ?array
    {
        $text = trim((string) self::get('branding.powered_by_text', ''));
        $url = trim((string) self::get('branding.powered_by_url', ''));

        return $text === '' ? null : ['text' => $text, 'url' => $url];
    }

    public static function assetUrl(string $key, ?string $fallback = null): ?string
    {
        $path = self::get($key);
        if (! is_string($path) || trim($path) === '') {
            return $fallback;
        }

        return Storage::disk('public')->url($path);
    }

    public static function cssVariables(): array
    {
        return [
            '--brand-primary' => self::get('theme.primary_color', self::get('branding.primary_color', '#d97706')),
            '--brand-accent' => self::get('theme.accent_color', self::get('branding.accent_color', '#f59e0b')),
            '--brand-sidebar' => self::get('theme.sidebar_color', self::get('branding.sidebar_color', '#101923')),
            '--brand-topbar' => self::get('theme.topbar_color', self::get('branding.topbar_color', '#ffffff')),
        ];
    }

    public static function companyProfile(): array
    {
        return [
            'name' => self::companyName(),
            'legal_name' => self::get('system.company_legal_name', self::companyName()),
            'address' => self::get('system.company_address', ''),
            'city' => self::get('system.company_city', ''),
            'province' => self::get('system.company_province', ''),
            'postal_code' => self::get('system.company_postal_code', ''),
            'phone' => self::get('system.company_phone', ''),
            'email' => self::get('system.company_email', ''),
            'website' => self::get('system.company_website', ''),
            'npwp' => self::get('system.company_npwp', ''),
            'pic' => self::get('system.company_pic', ''),
            'registration_no' => self::get('system.company_registration_no', ''),
        ];
    }
}
