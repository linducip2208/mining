<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Central authorization: every `can:{module}.{action}` check
        // resolves against our granular permission codes.
        // SUPER_ADMIN bypasses everything; others need the exact permission.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
            if ($user->hasPermission($ability)) {
                return true;
            }
            return null;
        });
    }
}
