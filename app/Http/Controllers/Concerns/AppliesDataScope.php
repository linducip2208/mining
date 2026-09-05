<?php

namespace App\Http\Controllers\Concerns;

/**
 * Enforces RBAC data scope (ALL_COMPANIES / COMPANY / SITE / OWN_DATA)
 * on list queries. Null = unrestricted (super admin / broad scope).
 */
trait AppliesDataScope
{
    protected function applySiteScope($query, string $column = 'site_id')
    {
        $user = auth()->user();
        if (!$user) {
            return $query;
        }
        $sites = $user->accessibleSiteIds();
        if ($sites !== null) {
            $query->whereIn($column, $sites);
        }
        return $query;
    }

    protected function applyCompanyScope($query, string $column = 'company_id')
    {
        $user = auth()->user();
        if (!$user) {
            return $query;
        }
        $companies = $user->accessibleCompanyIds();
        if ($companies !== null) {
            $query->whereIn($column, $companies);
        }
        return $query;
    }
}
