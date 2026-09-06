<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DocsScreenshot extends Command
{
    protected $signature = 'docs:screenshot
        {--only= : batasi ke modul tertentu (cth. sales,weighbridge)}
        {--dry : tampilkan daftar tanpa menjalankan browser}';

    protected $description = 'Ambil screenshot dokumentasi seluruh halaman penting via Playwright (headless Chromium)';

    public function handle(): int
    {
        $base = rtrim(config('app.url'), '/');
        $this->info("Target: {$base}");

        // pastikan server reachable
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $probe = @file_get_contents($base . '/login', false, $ctx);
        if ($probe === false || !str_contains((string) $probe, 'csrf-token')) {
            $this->error("Tidak dapat menjangkau {$base}/login. Pastikan web server berjalan.");
            return self::FAILURE;
        }

        $jobs = $this->buildJobs($base);
        if ($only = $this->option('only')) {
            $mods = explode(',', $only);
            $jobs = array_values(array_filter($jobs, fn ($j) => in_array(explode('/', $j['file'])[0], $mods)));
        }

        $this->info('Total screenshot: ' . count($jobs));
        if ($this->option('dry')) {
            foreach ($jobs as $j) {
                $this->line("  {$j['file']}  <=  {$j['url']}" . (isset($j['flow']) ? '  [flow]' : ''));
            }
            return self::SUCCESS;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'docsjobs') . '.json';
        file_put_contents($tmp, json_encode([
            'base' => $base,
            'user' => env('DOCS_USER', 'superadmin'),
            'pass' => env('DOCS_PASS', 'Admin!2345'),
            'out' => public_path('docs-assets/screenshots'),
            'jobs' => $jobs,
        ], JSON_UNESCAPED_SLASHES));

        $node = trim((string) shell_exec('where node 2>NUL')) ?: 'node';
        $node = explode("\n", $node)[0];
        $script = base_path('scripts/docs-screenshots.mjs');
        $cmd = '"' . $node . '" "' . $script . '" "' . $tmp . '" 2>&1';
        $this->info('Menjalankan Playwright...');
        exec($cmd, $output, $code);
        @unlink($tmp);

        $ok = 0;
        $fail = [];
        foreach ($output as $line) {
            if (str_starts_with($line, 'OK ')) {
                $ok++;
            } elseif (str_starts_with($line, 'FAIL ')) {
                $fail[] = substr($line, 5);
                $this->error($line);
            } else {
                $this->line($line);
            }
        }

        $this->info("Selesai: {$ok} sukses, " . count($fail) . ' gagal.');
        if ($fail) {
            $this->warn('Gagal: ' . implode(', ', $fail));
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    protected function firstId(string $table, string $column = 'id', array $where = [])
    {
        if (!Schema::hasTable($table)) {
            return null;
        }
        $q = \DB::table($table);
        foreach ($where as [$c, $v]) {
            $q->where($c, $v);
        }
        return $q->orderBy($column)->value($column);
    }

    protected function url(string $route, array $params = []): ?string
    {
        if (!Route::has($route)) {
            return null;
        }
        try {
            return route($route, $params, false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildJobs(string $base): array
    {
        $jobs = [];
        $shot = function (?string $url, string $file, array $extra = []) use (&$jobs, $base) {
            if (!$url) {
                return;
            }
            $jobs[] = array_merge(['url' => $base . $url, 'file' => $file], $extra);
        };
        $R = fn ($name, $params = []) => $this->url($name, $params);

        // target IDs dari data demo
        $maId = $this->firstId('mining_activities');
        $ticketPosted = $this->firstId('weighbridge_tickets', 'id', [['status', 'POSTED']])
            ?? $this->firstId('weighbridge_tickets', 'id', [['status', 'COMPLETE']])
            ?? $this->firstId('weighbridge_tickets');
        $batchId = $this->firstId('production_batches');
        $productId = \DB::table('items')->where('type', 'PRODUCT')->orderBy('id')->value('id');

        // 1. Getting started
        $shot('/login', 'dashboard/login.png', ['guest' => true]);
        $shot($R('dashboard'), 'dashboard/dashboard.png');

        // 2. Administration
        $shot($R('users.index'), 'administration/users.png');
        $shot($R('users.create'), 'administration/users-create.png');
        $shot($R('role.index'), 'administration/roles.png');
        $shot($R('role.create'), 'administration/roles-create.png');
        $shot($R('audit.index'), 'administration/audit.png');
        $shot($R('setting.index'), 'administration/settings.png');

        // 3. Organization
        $shot($R('companies.index'), 'organization/companies.png');
        $shot($R('branches.index'), 'organization/branches.png');
        $shot($R('sites.index'), 'organization/sites.png');
        $shot($R('divisions.index'), 'organization/divisions.png');

        // 4. HR
        $shot($R('employees.index'), 'hr/employees.png');
        $shot($R('attendances.index'), 'hr/attendances.png');
        $shot($R('leaves.index'), 'hr/leaves.png');
        $shot($R('overtimes.index'), 'hr/overtimes.png');
        $shot($R('payroll-runs.index'), 'hr/payroll.png');
        $shot($R('operator-incentives.index'), 'hr/incentives.png');

        // 5. Mining
        $shot($R('mining-activities.index'), 'mining/activity.png');
        $shot($R('mining-activities.create'), 'mining/activity-create.png');
        if ($maId) {
            $shot($R('mining-activities.show', ['mining_activity' => $maId]), 'mining/activity-detail.png');
        }

        // 6. Weighbridge (index/create/flow)
        $shot($R('weighbridge-tickets.index'), 'weighbridge/tickets.png');
        $shot($R('weighbridge-tickets.create'), 'weighbridge/first-weigh.png');
        $jobs[] = [
            'flow' => 'weighbridge',
            'createUrl' => $base . ($R('weighbridge-tickets.create') ?? '/weighbridge-tickets/create'),
            'postFirstUrl' => $base . '/weighbridge-tickets/first-weigh',
            'files' => [
                'ticket-detail' => 'weighbridge/ticket-detail.png',
                'second-weigh' => 'weighbridge/second-weigh.png',
                'posted-ticket' => 'weighbridge/posted-ticket.png',
                'print' => 'weighbridge/print.png',
            ],
        ];
        if ($ticketPosted) {
            $shot($R('weighbridge-tickets.show', ['weighbridge_ticket' => $ticketPosted]), 'weighbridge/posted-ticket.png');
            $printUrl = $R('weighbridge.print', ['weighbridge_ticket' => $ticketPosted]);
            if ($printUrl) {
                $shot($printUrl, 'weighbridge/print.png');
            }
        }

        // 7. Production
        $shot($R('production-batches.index'), 'production/batches.png');
        $shot($R('production-batches.create'), 'production/batches-create.png');
        if ($batchId) {
            $shot($R('production-batches.show', ['production_batch' => $batchId]), 'production/batch-detail.png');
        }

        // 8. Inventory
        $shot($R('items.index'), 'inventory/items.png');
        $shot($R('warehouses.index'), 'inventory/warehouses.png');
        $shot($R('stock.balance'), 'inventory/stock-balance.png');
        $shot($R('stock.card') . ($productId ? '?item_id=' . $productId : ''), 'inventory/stock-card.png');
        $shot($R('stock-transfers.index'), 'inventory/stock-transfers.png');
        $shot($R('stock-adjustments.index'), 'inventory/stock-adjustments.png');

        // 9. Procurement
        $shot($R('purchase-requests.index'), 'procurement/purchase-requests.png');
        $shot($R('purchase-orders.index'), 'procurement/purchase-orders.png');
        $shot($R('goods-receipts.index'), 'procurement/goods-receipts.png');
        $shot($R('vendor-bills.index'), 'procurement/vendor-bills.png');

        // 10. Sales
        $shot($R('customers.index'), 'sales/customers.png');
        $shot($R('sales-orders.index'), 'sales/sales-orders.png');
        $shot($R('delivery-orders.index'), 'sales/delivery-orders.png');
        $shot($R('invoices.index'), 'sales/invoices.png');
        $shot($R('payments.index'), 'sales/payments.png');
        $shot($R('deposit.index'), 'sales/deposits.png');
        $shot($R('price-lists.index'), 'sales/price-lists.png');
        $shot($R('price_variance.index'), 'sales/price-variances.png');

        // 11. Maintenance
        $shot($R('assets.index'), 'maintenance/assets.png');
        $shot($R('equipment.index'), 'maintenance/equipment.png');
        $shot($R('work-orders.index'), 'maintenance/work-orders.png');
        $shot($R('maintenance-schedules.index'), 'maintenance/schedules.png');

        // 12. Finance & accounting
        $shot($R('coa.index'), 'accounting/coa.png');
        $shot($R('journals.index'), 'accounting/journals.png');
        $shot($R('finance.trial_balance'), 'accounting/trial-balance.png');
        $coaId = $this->firstId('chart_of_accounts');
        $shot($R('finance.ledger') . ($coaId ? '?chart_of_account_id=' . $coaId : ''), 'accounting/ledger.png');
        $shot($R('finance.pl'), 'accounting/pl.png');
        $shot($R('finance.balance_sheet'), 'accounting/balance-sheet.png');
        $shot($R('finance.cash_flow'), 'accounting/cash-flow.png');
        $shot($R('finance.ar_aging'), 'accounting/ar-aging.png');
        $shot($R('finance.ap_aging'), 'accounting/ap-aging.png');
        $shot($R('tax.index'), 'accounting/tax.png');

        // 13. Documents & CSR
        $shot($R('documents.index'), 'corporate/documents.png');
        $shot($R('csr.index'), 'corporate/csr.png');

        // 14. Reports
        $shot($R('report.mining'), 'reports/mining.png');
        $shot($R('report.inventory'), 'reports/inventory.png');
        $shot($R('report.sales'), 'reports/sales.png');
        $shot($R('report.hr'), 'reports/hr.png');
        $shot($R('report.maintenance'), 'reports/maintenance.png');

        // 15. Approval
        $shot($R('approval.index'), 'approval/center.png');

        return array_values(array_filter($jobs));
    }
}
