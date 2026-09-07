<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Illuminate\Http\Response;

class PwaManifestController extends Controller
{
    public function __invoke(): Response
    {
        $enabled = filter_var(BrandingService::get('pwa.enabled', false), FILTER_VALIDATE_BOOLEAN);
        $icon192 = BrandingService::assetUrl('pwa.icon_192', url('/icons/icon-192.png'));
        $icon512 = BrandingService::assetUrl('pwa.icon_512', url('/icons/icon-512.png'));
        $icons = [
            ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => url('/icons/maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ];

        return response([
            'id' => '/dashboard',
            'name' => BrandingService::get('pwa.name', BrandingService::appName()),
            'short_name' => BrandingService::get('pwa.short_name', BrandingService::shortName()),
            'description' => BrandingService::get('pwa.description', BrandingService::tagline()),
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => $enabled ? 'standalone' : 'browser',
            'orientation' => 'any',
            'theme_color' => BrandingService::get('pwa.theme_color', '#0f172a'),
            'background_color' => BrandingService::get('pwa.background_color', '#ffffff'),
            'icons' => $icons,
        ])->header('Content-Type', 'application/manifest+json');
    }
}
