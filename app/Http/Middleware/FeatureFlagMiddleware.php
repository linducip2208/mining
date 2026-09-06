<?php

namespace App\Http\Middleware;

use App\Support\FeatureFlag;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureFlagMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $module = FeatureFlag::moduleForRoute($request->route()?->getName());
        if ($module && ! FeatureFlag::enabled($module)) {
            abort(404, 'Modul ini sedang dinonaktifkan oleh administrator.');
        }

        return $next($request);
    }
}
