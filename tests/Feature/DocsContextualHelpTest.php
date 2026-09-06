<?php

namespace Tests\Feature;

use App\Docs\DocRegistry;
use Tests\TestCase;

/**
 * Contextual help: "?" di aplikasi membuka docs yang sesuai route aktif.
 */
class DocsContextualHelpTest extends TestCase
{
    public function test_key_routes_map_to_live_docs(): void
    {
        $routes = [
            'users.index', 'fuel-issues.index', 'stockpiles.index', 'dispatch.dashboard',
            'budgets.index', 'hse.reports.index', 'samples.index', 'telematics.index',
            'weighbridge.devices.index', 'forecast.index', 'ai.index', 'approval.index',
        ];
        foreach ($routes as $r) {
            $url = DocRegistry::urlForRoute($r);
            $this->assertNotEquals('/docs', $url, "Route {$r} belum dipetakan");
            $this->get($url)->assertStatus(200);
        }
    }

    public function test_unknown_route_falls_back_to_docs_home(): void
    {
        $this->assertEquals('/docs', DocRegistry::urlForRoute('route-yang-tidak-ada'));
        $this->get('/docs')->assertStatus(200);
    }

    public function test_inverse_mapping_consistent(): void
    {
        $bad = [];
        foreach (['/docs/fuel/issues', '/docs/stockpile/board', '/docs/hse/dashboard'] as $url) {
            $route = DocRegistry::appRouteForDoc($url);
            if (!$route || !\Illuminate\Support\Facades\Route::has($route)) {
                $bad[] = $url;
            }
        }
        $this->assertEmpty($bad, 'Inverse mapping rusak: ' . implode(', ', $bad));
    }

    public function test_role_guides_exist_and_render(): void
    {
        $count = 0;
        foreach (DocRegistry::sections()['roles']['pages'] as $slug => $p) {
            $this->get('/docs/roles/' . $slug)->assertStatus(200);
            $count++;
        }
        $this->assertGreaterThanOrEqual(11, $count);
    }
}
