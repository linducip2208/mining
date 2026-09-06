<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthController extends Controller
{
    public function index()
    {
        $checks = [
            'database' => $this->check(fn () => DB::connection()->getPdo() !== null),
            'cache' => $this->check(fn () => Cache::put('health-check', true, 5) && Cache::get('health-check') === true),
            'storage' => is_writable(storage_path()),
            'queue' => Schema::hasTable('jobs') || ! config('queue.default') || config('queue.default') === 'sync',
            'scheduler' => file_exists(base_path('routes/console.php')),
            'mail' => filled(config('mail.default')),
        ];

        return view('settings.health', [
            'checks' => $checks,
            'versions' => [
                'Application' => config('app.name'),
                'PHP' => PHP_VERSION,
                'Laravel' => app()->version(),
                'Database' => DB::getDriverName(),
            ],
        ]);
    }

    private function check(callable $callback): bool
    {
        try {
            return (bool) $callback();
        } catch (\Throwable) {
            return false;
        }
    }
}
