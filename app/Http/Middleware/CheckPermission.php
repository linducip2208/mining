<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->status !== 'ACTIVE') {
            auth()->logout();
            return redirect()->route('login')->withErrors(['username' => 'Akun Anda tidak aktif.']);
        }

        if ($user->force_password_reset && !$request->routeIs('password.change', 'password.update', 'logout')) {
            return redirect()->route('password.change');
        }

        // permission from explicit parameter or route name "module.action"
        $required = $permission ?: $request->route()->getName();
        // map route name: sales.orders.create -> sales.order.create permission base
        if ($required && !$this->authorized($user, $required)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }

    protected function authorized($user, string $required): bool
    {
        if ($user->hasPermission($required)) {
            return true;
        }
        // allow variant: route "a.b.c" vs permission "a.b.view|index"
        $parts = explode('.', $required);
        $last = array_pop($parts);
        $base = implode('.', $parts);
        $aliases = match ($last) {
            'index', 'show' => ['view'],
            'store', 'create' => ['create'],
            'update', 'edit' => ['update'],
            'destroy' => ['delete'],
            default => [],
        };
        foreach ($aliases as $alias) {
            if ($user->hasPermission($base . '.' . $alias)) {
                return true;
            }
        }
        return false;
    }
}
