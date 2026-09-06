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

        // data scope on route-bound records (IDOR protection)
        $this->enforceDataScope($request, $user);

        return $next($request);
    }

    /**
     * Abort 403 when a route-bound record (or its scope parent) lies
     * outside the user's company/site scope. Central IDOR guard.
     */
    protected function enforceDataScope(Request $request, $user): void
    {
        $route = $request->route();
        if (!$route) {
            return;
        }
        foreach ($route->parameters() as $param) {
            if (!$param instanceof \Illuminate\Database\Eloquent\Model) {
                continue;
            }
            $record = $this->scopeTarget($param);
            if (!$record) {
                continue;
            }
            // OWN_DATA: record must belong to the user (takes precedence over org scopes)
            if ($user->isOwnDataOnly()) {
                if ($record->getAttribute('created_by') === null
                    || (int) $record->getAttribute('created_by') !== (int) $user->id) {
                    abort(403, 'Data di luar scope akses Anda.');
                }
                continue;
            }
            $siteId = $record->getAttribute('site_id');
            if ($siteId !== null && !$user->canSeeSite($siteId)) {
                abort(403, 'Data di luar scope akses Anda.');
            }
            $companyId = $record->getAttribute('company_id');
            if ($companyId !== null) {
                $companies = $user->accessibleCompanyIds();
                if ($companies !== null && !in_array($companyId, $companies)) {
                    abort(403, 'Data di luar scope akses Anda.');
                }
            }
            // branch scope: direct attribute or derived from site
            $branchId = $record->getAttribute('branch_id');
            if ($branchId === null && $siteId !== null && method_exists($user, 'accessibleBranchIds')) {
                $branchId = \App\Models\Site::whereKey($siteId)->value('branch_id');
            }
            if ($branchId !== null) {
                $branches = $user->accessibleBranchIds();
                if ($branches !== null && !in_array($branchId, $branches)) {
                    abort(403, 'Data di luar scope akses Anda.');
                }
            }
            // division / department scope (HR & document records)
            foreach (['division_id' => 'accessibleDivisionIds', 'department_id' => 'accessibleDepartmentIds'] as $attr => $method) {
                $val = $record->getAttribute($attr);
                if ($val !== null) {
                    $allowed = $user->$method();
                    if ($allowed !== null && !in_array($val, $allowed)) {
                        abort(403, 'Data di luar scope akses Anda.');
                    }
                }
            }
        }
    }

    protected function scopeTarget($record)
    {
        return match (true) {
            $record instanceof \App\Models\DeliveryOrder => $record->salesOrder,
            $record instanceof \App\Models\GoodsReceipt => $record->purchaseOrder,
            $record instanceof \App\Models\VendorBill => $record->supplier,
            $record instanceof \App\Models\Leave => $record->employee,
            $record instanceof \App\Models\Attendance => $record->employee,
            $record instanceof \App\Models\PayrollDetail => $record->run,
            $record instanceof \App\Models\MaintenancePart => $record->workOrder,
            $record instanceof \App\Models\CustomerDeposit => $record->customer,
            default => $record,
        };
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
