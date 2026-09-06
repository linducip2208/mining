<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Illuminate\Http\Response;

class PwaManifestController extends Controller
{
    public function __invoke(): Response
    {
        $enabled = filter_var(BrandingService::get('pwa.enabled', false), FILTER_VALIDATE_BOOLEAN);
        $icon192 = BrandingService::assetUrl('pwa.icon_192');
        $icon512 = BrandingService::assetUrl('pwa.icon_512');
        $icons = collect([
            ['src' => $icon192, 'sizes' => '192x192'],
            ['src' => $icon512, 'sizes' => '512x512'],
        ])->filter(fn ($icon) => filled($icon['src']))->values()->all();

        return response([
            'name' => BrandingService::get('pwa.name', BrandingService::appName()),
            'short_name' => BrandingService::get('pwa.short_name', BrandingService::shortName()),
            'description' => BrandingService::get('pwa.description', BrandingService::tagline()),
            'start_url' => '/dashboard',
            'display' => $enabled ? 'standalone' : 'browser',
            'theme_color' => BrandingService::get('pwa.theme_color', '#0f172a'),
            'background_color' => BrandingService::get('pwa.background_color', '#ffffff'),
            'icons' => $icons,
        ])->header('Content-Type', 'application/manifest+json');
    }
}
