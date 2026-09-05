<?php

namespace App\Http\Controllers\Concerns;

/**
 * Enforces RBAC data scope (ALL_COMPANIES / COMPANY / SITE / OWN_DATA).
 * - apply*Scope(): filter list queries (index).
 * - ensureInScope(): abort 403 when a record (or its parent) is outside scope.
 * - ensureCompanyInScope()/ensureSiteInScope(): guard IDs coming from request body.
 * Null scope values = unrestricted (super admin / broad scope).
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

    /**
     * Abort 403 if the given record (or its scope parent) is outside
     * the current user's data scope. Checks BOTH site and company.
     */
    protected function ensureInScope($record): void
    {
        $user = auth()->user();
        if (!$user || !$record) {
            return;
        }
        $record = $this->scopeTarget($record);
        if (!$record) {
            return;
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
    }

    protected function ensureCompanyInScope($companyId): void
    {
        if ($companyId === null) {
            return;
        }
        $companies = auth()->user()?->accessibleCompanyIds();
        if ($companies !== null && !in_array($companyId, $companies)) {
            abort(403, 'Perusahaan di luar scope akses Anda.');
        }
    }

    protected function ensureSiteInScope($siteId): void
    {
        if ($siteId === null) {
            return;
        }
        if (!auth()->user()?->canSeeSite($siteId)) {
            abort(403, 'Site di luar scope akses Anda.');
        }
    }

    /**
     * Resolve the scope-bearing record for models that only carry
     * a reference to a scoped parent.
     */
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
}
