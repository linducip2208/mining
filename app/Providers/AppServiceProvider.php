<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        // Apply tenant-independent runtime preferences without allowing
        // arbitrary config keys from the request to reach the framework.
        try {
            if (Schema::hasTable('settings')) {
                $timezone = (string) Setting::get('general.timezone', config('app.timezone', 'Asia/Jakarta'));
                if (in_array($timezone, timezone_identifiers_list(), true)) {
                    date_default_timezone_set($timezone);
                    config(['app.timezone' => $timezone]);
                }
                $locale = (string) Setting::get('general.locale', 'id');
                if (in_array($locale, ['id', 'en'], true)) {
                    app()->setLocale($locale);
                }
                config(['app.currency' => strtoupper((string) Setting::get('finance.default_currency', Setting::get('general.default_currency', 'IDR')))]);
                $timeout = (int) Setting::get('security.session_timeout', config('session.lifetime', 120));
                if ($timeout >= 5 && $timeout <= 10080) {
                    config(['session.lifetime' => $timeout]);
                }
                $senderAddress = Setting::get('email.sender_address');
                $senderName = Setting::get('email.sender_name');
                $smtpHost = Setting::get('email.smtp_host');
                if (filled($senderAddress)) {
                    config(['mail.from.address' => $senderAddress]);
                }
                if (filled($senderName)) {
                    config(['mail.from.name' => $senderName]);
                }
                if (filled($smtpHost)) {
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.host' => $smtpHost,
                        'mail.mailers.smtp.port' => (int) Setting::get('email.smtp_port', 587),
                        'mail.mailers.smtp.username' => Setting::get('email.smtp_username'),
                        'mail.mailers.smtp.password' => Setting::get('email.smtp_password'),
                        'mail.mailers.smtp.encryption' => Setting::get('email.smtp_encryption', 'tls'),
                    ]);
                }
            }
        } catch (\Throwable) {
            // Settings are not available during first install/migrations.
        }

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

        // Blade: allow importing services in views (e.g. @if(ImportService::fileSeenBefore(...)))
        Blade::if('fileImportedBefore', function (string $hash) {
            return ImportService::fileSeenBefore($hash);
        });
    }
}
