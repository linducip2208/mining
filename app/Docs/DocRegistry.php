<?php

namespace App\Docs;

use App\Docs\Content\Part1;
use App\Docs\Content\Part2;
use App\Docs\Content\Part3;
use App\Docs\Content\Part4;
use App\Docs\Content\Part5;
use App\Docs\Content\Part6;

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
                Part5::pages(),
                Part6::pages(),
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

    /**
     * Kelompok tampilan homepage + ikon + deskripsi per section.
     * group: nama kelompok di homepage. status: kelengkapan docs.
     */
    public static function groups(): array
    {
        return [
            'Mulai Cepat' => ['getting-started'],
            'Administrasi' => ['administration'],
            'Organisasi' => ['organization'],
            'HR & Payroll' => ['hr'],
            'Operasi Tambang' => ['mining', 'weighbridge', 'production'],
            'Armada & Alat Berat' => ['fleet'],
            'Manajemen BBM' => ['fuel'],
            'Manajemen Ban' => ['tire'],
            'Dispatch & Hauling' => ['dispatch', 'hauling'],
            'Stockpile' => ['stockpile'],
            'Quality & Lab' => ['quality'],
            'Inventory & Pengadaan' => ['inventory', 'procurement'],
            'Penjualan & Kontrak' => ['sales', 'contract'],
            'Budget & Biaya' => ['budget', 'cost'],
            'Keuangan & Akuntansi' => ['finance'],
            'HSE & Compliance' => ['hse', 'compliance'],
            'Dokumen & CSR' => ['documents'],
            'Laporan' => ['reports'],
            'AI & Analitik' => ['analytics'],
            'Integrasi & Telematika' => ['telematics', 'iot-weighbridge'],
            'Persetujuan & Audit' => ['approval', 'audit'],
            'Alur Kerja' => ['workflows'],
            'Panduan Peran' => ['roles'],
            'Bantuan' => ['faq', 'troubleshooting'],
        ];
    }

    public static function sectionMeta(string $slug): array
    {
        return [
            'getting-started' => ['icon' => 'star', 'blurb' => 'Login, dashboard, navigasi, dan konsep dasar untuk pengguna baru.'],
            'administration' => ['icon' => 'sliders', 'blurb' => 'Pengguna, peran, audit trail, dan pengaturan sistem.'],
            'organization' => ['icon' => 'building', 'blurb' => 'Struktur perusahaan, cabang, site, dan divisi.'],
            'hr' => ['icon' => 'user', 'blurb' => 'Karyawan, absensi, cuti, lembur, dan payroll.'],
            'mining' => ['icon' => 'pickaxe', 'blurb' => 'Aktivitas penambangan harian per pit dan shift.'],
            'weighbridge' => ['icon' => 'scale', 'blurb' => 'Timbang tiket, validasi, dan integrasi jembatan.'],
            'production' => ['icon' => 'factory', 'blurb' => 'Batch crusher, output, losses, dan approval.'],
            'fleet' => ['icon' => 'truck', 'blurb' => 'Unit, HM/odometer, inspeksi, ketersediaan, dan biaya.'],
            'fuel' => ['icon' => 'fuel', 'blurb' => 'Tangki, penerimaan, issue, dip, dan anomali konsumsi.'],
            'tire' => ['icon' => 'tire', 'blurb' => 'Lifecycle ban dari stok hingga scrap.'],
            'dispatch' => ['icon' => 'flag', 'blurb' => 'Ritase, titik muat/bongkar, dan cycle time.'],
            'hauling' => ['icon' => 'truck', 'blurb' => 'Rute, kontrak angkut, dan settlement tarif.'],
            'stockpile' => ['icon' => 'warehouse', 'blurb' => 'Saldo pile, survei, dan rekonsiliasi.'],
            'quality' => ['icon' => 'flask', 'blurb' => 'Sampel lab, spesifikasi, hold, dan CoA.'],
            'inventory' => ['icon' => 'box', 'blurb' => 'Item, gudang, saldo, dan kartu stok.'],
            'procurement' => ['icon' => 'cart', 'blurb' => 'PR, PO, penerimaan, dan tagihan vendor.'],
            'sales' => ['icon' => 'money', 'blurb' => 'Customer, SO, DO, faktur, dan pembayaran.'],
            'contract' => ['icon' => 'book', 'blurb' => 'Kontrak customer/supplier/hauling dan realisasi.'],
            'budget' => ['icon' => 'wallet', 'blurb' => 'Pagu, komitmen, aktual, dan kontrol over-budget.'],
            'cost' => ['icon' => 'chart', 'blurb' => 'Mesin biaya per ton dan komponennya.'],
            'finance' => ['icon' => 'bank', 'blurb' => 'COA, jurnal, laporan keuangan, dan pajak.'],
            'hse' => ['icon' => 'heart', 'blurb' => 'Insiden, corrective action, dan permit kerja.'],
            'compliance' => ['icon' => 'check-circle', 'blurb' => 'Register izin, pengingat, dan kalender.'],
            'documents' => ['icon' => 'file', 'blurb' => 'Dokumen legal dan program CSR.'],
            'reports' => ['icon' => 'chart', 'blurb' => 'Laporan konsolidasi siap cetak dan ekspor.'],
            'analytics' => ['icon' => 'cpu', 'blurb' => 'Forecast, deteksi anomali, AI copilot, dan executive dashboard.'],
            'telematics' => ['icon' => 'radio', 'blurb' => 'Provider GPS dan event telemetri.'],
            'iot-weighbridge' => ['icon' => 'cpu', 'blurb' => 'Perangkat timbangan dan API bridge.'],
            'approval' => ['icon' => 'check-circle', 'blurb' => 'Alur persetujuan multi-level di approval center.'],
            'audit' => ['icon' => 'audit', 'blurb' => 'Jejak audit seluruh aktivitas sensitif.'],
            'workflows' => ['icon' => 'layers', 'blurb' => 'Alur end-to-end: mine-to-cash, procure-to-pay.'],
            'roles' => ['icon' => 'graduation', 'blurb' => 'Panduan per peran: apa yang dibuka setiap hari.'],
            'faq' => ['icon' => 'help', 'blurb' => 'Pertanyaan yang sering diajukan.'],
            'troubleshooting' => ['icon' => 'wrench', 'blurb' => 'Solusi error umum langkah demi langkah.'],
        ][$slug] ?? ['icon' => 'box', 'blurb' => 'Panduan modul.'];
    }

    public static function sectionStats(string $slug, array $section): array
    {
        $pages = $section['pages'] ?? [];
        $shots = 0;
        foreach ($pages as $p) {
            if (!empty($p['shot']) && file_exists(public_path('docs-assets/screenshots/' . ltrim($p['shot'], '/')))) {
                $shots++;
            }
        }
        $total = count($pages);
        return [
            'tutorials' => $total,
            'screenshots' => $shots,
            'popular' => array_slice(array_keys($pages), 0, 4),
            'complete' => $total > 0 && $shots >= $total,
        ];
    }

    /** Kategori hasil pencarian. */
    public static function categoryFor(string $section): string
    {
        return match ($section) {
            'faq' => 'FAQ',
            'troubleshooting' => 'Troubleshooting',
            'workflows' => 'Workflow',
            default => 'Tutorial',
        };
    }

    /** Peran yang disarankan berdasarkan permission halaman. */
    public static function roleFor(?string $permission): string
    {
        $p = strtolower($permission ?? '');
        return match (true) {
            str_contains($p, 'fuel') => 'Fuel Admin, Fleet Manager',
            str_contains($p, 'fleet') || str_contains($p, 'tire') => 'Fleet Manager, Mekanik',
            str_contains($p, 'dispatch') => 'Dispatcher, Mine Manager',
            str_contains($p, 'weighbridge') => 'Operator Timbangan',
            str_contains($p, 'mining') || str_contains($p, 'production') => 'Mine Manager, Operator',
            str_contains($p, 'stockpile') || str_contains($p, 'quality') => 'QC, Mine Manager',
            str_contains($p, 'sales') || str_contains($p, 'contract') => 'Sales, Contract Manager',
            str_contains($p, 'purchase') || str_contains($p, 'procurement') || str_contains($p, 'vendor') || str_contains($p, 'goods') => 'Purchasing, Gudang',
            str_contains($p, 'inventory') || str_contains($p, 'stock') => 'Gudang, Site Manager',
            str_contains($p, 'payroll') || str_contains($p, 'employee') || str_contains($p, 'attendance') || str_contains($p, 'leave') || str_contains($p, 'overtime') => 'HR, Payroll Staff',
            str_contains($p, 'journal') || str_contains($p, 'ledger') || str_contains($p, 'finance') || str_contains($p, 'budget') || str_contains($p, 'cost') || str_contains($p, 'tax') => 'Finance, Accounting',
            str_contains($p, 'hse') => 'HSE Officer, Site Manager',
            str_contains($p, 'compliance') || str_contains($p, 'document') => 'Compliance, Document Control',
            str_contains($p, 'user') || str_contains($p, 'role') || str_contains($p, 'setting') => 'Super Admin',
            str_contains($p, 'audit') => 'Auditor, Super Admin',
            default => 'Semua peran',
        };
    }

    /** Tingkat kesulitan dari konten halaman. */
    public static function difficultyFor(array $page): string
    {
        $hay = strtolower(($page['workflow'] ?? '') . ' ' . ($page['permission'] ?? ''));
        return match (true) {
            str_contains($hay, 'approve') || str_contains($hay, 'override') || str_contains($hay, 'reversal') => 'Menengah',
            str_contains($hay, 'engine') || str_contains($hay, 'allocation') => 'Lanjutan',
            default => 'Dasar',
        };
    }

    /**
     * Balikan urlForRoute: URL docs -> nama route aplikasi (untuk "Buka di Aplikasi").
     */
    public static function appRouteForDoc(string $docUrl): ?string
    {
        static $inv = null;
        if ($inv === null) {
            $inv = [];
            // peta route->docs terpusat agar konsisten dua arah
            foreach (self::routeDocMap() as $route => $url) {
                $inv[$url] = $route;
            }
        }
        return $inv[$docUrl] ?? null;
    }

    public static function routeDocMap(): array
    {
        return [
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
            'mining.dashboard' => '/docs/mining/activity',
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
            'fleet.dashboard' => '/docs/fleet/dashboard',
            'fleet.meters' => '/docs/fleet/meters',
            'fleet.inspections' => '/docs/fleet/inspections',
            'vehicles.index' => '/docs/fleet/vehicles',
            'fuel.dashboard' => '/docs/fuel/dashboard',
            'fuel-receipts.index' => '/docs/fuel/receipts',
            'fuel-issues.index' => '/docs/fuel/issues',
            'fuel-dips.index' => '/docs/fuel/dips',
            'tires.index' => '/docs/tire/index',
            'dispatch.dashboard' => '/docs/dispatch/board',
            'dispatch.trips.index' => '/docs/dispatch/trips',
            'loading-points.index' => '/docs/dispatch/master',
            'hauling-contracts.index' => '/docs/hauling/contracts',
            'stockpiles.dashboard' => '/docs/stockpile/board',
            'stockpiles.index' => '/docs/stockpile/detail',
            'samples.index' => '/docs/quality/samples',
            'specs.index' => '/docs/quality/specs',
            'quality-holds.index' => '/docs/quality/holds',
            'cost.dashboard' => '/docs/cost/dashboard',
            'cost.others.index' => '/docs/cost/manual',
            'customer-contracts.index' => '/docs/contract/customers',
            'supplier-contracts.index' => '/docs/contract/suppliers',
            'report.contract' => '/docs/contract/realization',
            'budgets.index' => '/docs/budget/index',
            'report.budget' => '/docs/budget/vs-actual',
            'hse.dashboard' => '/docs/hse/dashboard',
            'hse.reports.index' => '/docs/hse/reports',
            'hse.permits.index' => '/docs/hse/permits',
            'compliance.index' => '/docs/compliance/register',
            'compliance.calendar' => '/docs/compliance/calendar',
            'telematics.index' => '/docs/telematics/overview',
            'weighbridge.devices.index' => '/docs/iot-weighbridge/devices',
            'forecast.index' => '/docs/analytics/forecast',
            'ai.index' => '/docs/analytics/ai-copilot',
            'executive.index' => '/docs/analytics/executive',
        ];
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
        return self::routeDocMap()[$routeName] ?? '/docs';
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
