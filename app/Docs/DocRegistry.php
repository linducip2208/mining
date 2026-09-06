<?php

namespace App\Docs;

use App\Docs\Content\Part1;
use App\Docs\Content\Part2;
use App\Docs\Content\Part3;
use App\Docs\Content\Part4;

class DocRegistry
{
    protected static ?array $sections = null;

    public static function sections(): array
    {
        if (self::$sections === null) {
            self::$sections = array_merge(
                Part1::pages(),
                Part2::pages(),
                Part3::pages(),
                Part4::pages(),
            );
        }
        return self::$sections;
    }

    public static function page(string $section, string $page): ?array
    {
        $s = self::sections()[$section] ?? null;
        if (!$s) {
            return null;
        }
        $p = $s['pages'][$page] ?? null;
        if (!$p) {
            return null;
        }
        $p['section'] = $section;
        $p['slug'] = $page;
        $p['url'] = "/docs/{$section}/{$page}";
        return $p;
    }

    public static function allPages(): array
    {
        $out = [];
        foreach (self::sections() as $section => $s) {
            foreach ($s['pages'] as $slug => $p) {
                $p['section'] = $section;
                $p['slug'] = $slug;
                $p['url'] = "/docs/{$section}/{$slug}";
                $out[] = $p;
            }
        }
        return $out;
    }

    public static function prevNext(string $section, string $page): array
    {
        $flat = array_values(self::allPages());
        $idx = null;
        foreach ($flat as $i => $p) {
            if ($p['section'] === $section && $p['slug'] === $page) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            return [null, null];
        }
        return [$flat[$idx - 1] ?? null, $flat[$idx + 1] ?? null];
    }

    public static function screenshotUrl(array $page): ?string
    {
        if (empty($page['shot'])) {
            return null;
        }
        return '/docs-assets/screenshots/' . ltrim($page['shot'], '/');
    }

    public static function search(string $q): array
    {
        $q = mb_strtolower(trim($q));
        if (mb_strlen($q) < 2) {
            return [];
        }
        $terms = preg_split('/\s+/', $q);
        $hits = [];
        foreach (self::allPages() as $p) {
            $hay = mb_strtolower(implode(' ', [
                $p['title'] ?? '', $p['module'] ?? '', $p['purpose'] ?? '',
                $p['keywords'] ?? '', implode(' ', $p['steps'] ?? []),
                implode(' ', $p['tips'] ?? []),
            ]));
            $score = 0;
            foreach ($terms as $t) {
                if (str_contains($hay, $t)) {
                    $score += mb_strpos(mb_strtolower($p['title'] ?? ''), $t) !== false ? 3 : 1;
                } else {
                    $score = -1;
                    break;
                }
            }
            if ($score > 0) {
                $hits[] = ['page' => $p, 'score' => $score];
            }
        }
        usort($hits, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($hits, 0, 20);
    }

    /**
     * Contextual help: peta route aplikasi -> halaman docs.
     */
    public static function urlForRoute(?string $routeName): string
    {
        $map = [
            'dashboard' => '/docs/getting-started/dashboard',
            'users.index' => '/docs/administration/users',
            'role.index' => '/docs/administration/roles',
            'audit.index' => '/docs/administration/audit',
            'setting.index' => '/docs/administration/settings',
            'companies.index' => '/docs/organization/companies',
            'branches.index' => '/docs/organization/branches',
            'sites.index' => '/docs/organization/sites',
            'divisions.index' => '/docs/organization/divisions',
            'employees.index' => '/docs/hr/employees',
            'attendances.index' => '/docs/hr/attendances',
            'leaves.index' => '/docs/hr/leaves',
            'overtimes.index' => '/docs/hr/overtimes',
            'payroll-runs.index' => '/docs/hr/payroll',
            'operator-incentives.index' => '/docs/hr/incentives',
            'mining-activities.index' => '/docs/mining/activity',
            'weighbridge-tickets.index' => '/docs/weighbridge/tickets',
            'production-batches.index' => '/docs/production/batches',
            'items.index' => '/docs/inventory/items',
            'warehouses.index' => '/docs/inventory/warehouses',
            'stock.balance' => '/docs/inventory/stock-balance',
            'stock.card' => '/docs/inventory/stock-card',
            'stock-transfers.index' => '/docs/inventory/stock-transfers',
            'stock-adjustments.index' => '/docs/inventory/stock-adjustments',
            'purchase-requests.index' => '/docs/procurement/purchase-requests',
            'purchase-orders.index' => '/docs/procurement/purchase-orders',
            'goods-receipts.index' => '/docs/procurement/goods-receipts',
            'vendor-bills.index' => '/docs/procurement/vendor-bills',
            'customers.index' => '/docs/sales/customers',
            'sales-orders.index' => '/docs/sales/sales-orders',
            'delivery-orders.index' => '/docs/sales/delivery-orders',
            'invoices.index' => '/docs/sales/invoices',
            'payments.index' => '/docs/sales/payments',
            'deposit.index' => '/docs/sales/deposits',
            'price-lists.index' => '/docs/sales/price-lists',
            'price_variance.index' => '/docs/sales/price-variances',
            'assets.index' => '/docs/maintenance/assets',
            'equipment.index' => '/docs/maintenance/equipment',
            'work-orders.index' => '/docs/maintenance/work-orders',
            'maintenance-schedules.index' => '/docs/maintenance/schedules',
            'coa.index' => '/docs/finance/coa',
            'journals.index' => '/docs/finance/journals',
            'finance.trial_balance' => '/docs/finance/trial-balance',
            'finance.ledger' => '/docs/finance/ledger',
            'finance.pl' => '/docs/finance/pl',
            'finance.balance_sheet' => '/docs/finance/balance-sheet',
            'finance.cash_flow' => '/docs/finance/cash-flow',
            'finance.ar_aging' => '/docs/finance/ar-aging',
            'finance.ap_aging' => '/docs/finance/ap-aging',
            'tax.index' => '/docs/finance/tax',
            'documents.index' => '/docs/documents/documents',
            'csr.index' => '/docs/documents/csr',
            'report.mining' => '/docs/reports/mining',
            'report.inventory' => '/docs/reports/inventory',
            'report.sales' => '/docs/reports/sales',
            'report.hr' => '/docs/reports/hr',
            'report.maintenance' => '/docs/reports/maintenance',
            'approval.index' => '/docs/approval/center',
        ];
        return $map[$routeName] ?? '/docs';
    }

    public static function isPublic(): bool
    {
        try {
            if (class_exists(\App\Models\Setting::class)) {
                $setting = \App\Models\Setting::get('docs.public');
                if ($setting !== null && $setting !== '') {
                    return filter_var($setting, FILTER_VALIDATE_BOOL);
                }
            }
        } catch (\Throwable $e) {
            // DB belum siap (install/testing) → fallback ke env
        }
        return filter_var(env('DOCS_PUBLIC', true), FILTER_VALIDATE_BOOL);
    }
}
