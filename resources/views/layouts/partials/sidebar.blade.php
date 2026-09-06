@php
    // Enterprise sidebar: icon per item, collapsible groups, permission-aware.
    // Collapsed mode (72px) via <html class="sb-collapsed">, persisted in localStorage.
    $sidebarMenu = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'dashboard', 'perm' => 'dashboard.view'],
        ['group' => 'Administrasi', 'icon' => 'sliders', 'items' => [
            ['label' => 'Manajemen Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'perm' => 'user.view', 'icon' => 'users'],
            ['label' => 'Manajemen Peran', 'route' => 'role.index', 'active' => 'role.*', 'perm' => 'role.view', 'icon' => 'shield'],
            ['label' => 'Audit Trail', 'route' => 'audit.index', 'active' => 'audit.*', 'perm' => 'audit.view', 'icon' => 'audit'],
            ['label' => 'Pengaturan Sistem', 'route' => 'setting.index', 'active' => 'setting.*', 'perm' => 'setting.view', 'icon' => 'cog'],
        ]],
        ['group' => 'Organisasi', 'icon' => 'building', 'items' => [
            ['label' => 'Perusahaan', 'route' => 'companies.index', 'active' => 'companies.*', 'perm' => 'company.view', 'icon' => 'building'],
            ['label' => 'Cabang', 'route' => 'branches.index', 'active' => 'branches.*', 'perm' => 'branch.view', 'icon' => 'branch'],
            ['label' => 'Site Tambang', 'route' => 'sites.index', 'active' => 'sites.*', 'perm' => 'site.view', 'icon' => 'site'],
            ['label' => 'Divisi & Departemen', 'route' => 'divisions.index', 'active' => ['divisions.*', 'departments.*'], 'perm' => 'division.view', 'icon' => 'division'],
        ]],
        ['group' => 'HR & Payroll', 'icon' => 'user', 'items' => [
            ['label' => 'Karyawan', 'route' => 'employees.index', 'active' => 'employees.*', 'perm' => 'employee.view', 'icon' => 'user'],
            ['label' => 'Absensi', 'route' => 'attendances.index', 'active' => 'attendances.*', 'perm' => 'attendance.view', 'icon' => 'clock'],
            ['label' => 'Cuti', 'route' => 'leaves.index', 'active' => 'leaves.*', 'perm' => 'leave.view', 'icon' => 'calendar'],
            ['label' => 'Lembur', 'route' => 'overtimes.index', 'active' => 'overtimes.*', 'perm' => 'overtime.view', 'icon' => 'clock'],
            ['label' => 'Payroll', 'route' => 'payroll-runs.index', 'active' => ['payroll-runs.*', 'payroll.*'], 'perm' => 'payroll.view', 'icon' => 'wallet'],
            ['label' => 'Insentif Operator', 'route' => 'operator-incentives.index', 'active' => 'operator-incentives.*', 'perm' => 'incentive.view', 'icon' => 'star'],
        ]],
        ['group' => 'Operasi Tambang', 'icon' => 'pickaxe', 'items' => [
            ['label' => 'Dashboard Operasi', 'route' => 'mining.dashboard', 'active' => ['mining.dashboard'], 'perm' => 'mining.view', 'icon' => 'dashboard'],
            ['label' => 'Aktivitas Tambang', 'route' => 'mining-activities.index', 'active' => ['mining-activities.*', 'mining.*'], 'perm' => 'mining.view', 'icon' => 'pickaxe'],
            ['label' => 'Timbangan', 'route' => 'weighbridge-tickets.index', 'active' => ['weighbridge-tickets.*', 'weighbridge.*'], 'perm' => 'weighbridge.view', 'icon' => 'scale'],
            ['label' => 'Perangkat Timbangan', 'route' => 'weighbridge.devices.index', 'active' => 'weighbridge.devices.*', 'perm' => 'weighbridge.view', 'icon' => 'cpu'],
            ['label' => 'Produksi Crusher', 'route' => 'production-batches.index', 'active' => ['production-batches.*', 'production.*'], 'perm' => 'production.view', 'icon' => 'factory'],
            ['label' => 'Dispatch Board', 'route' => 'dispatch.dashboard', 'active' => ['dispatch.dashboard'], 'perm' => 'dispatch.view', 'icon' => 'truck'],
            ['label' => 'Trip Dispatch', 'route' => 'dispatch.trips.index', 'active' => ['dispatch.trips.*'], 'perm' => 'dispatch.view', 'icon' => 'flag'],
            ['label' => 'Titik Muat', 'route' => 'loading-points.index', 'active' => 'loading-points.*', 'perm' => 'dispatch.view', 'icon' => 'arrow-right'],
            ['label' => 'Titik Bongkar', 'route' => 'dumping-points.index', 'active' => 'dumping-points.*', 'perm' => 'dispatch.view', 'icon' => 'arrow-left'],
            ['label' => 'Rute Hauling', 'route' => 'hauling-routes.index', 'active' => 'hauling-routes.*', 'perm' => 'dispatch.view', 'icon' => 'radio'],
            ['label' => 'Stockpile Board', 'route' => 'stockpiles.dashboard', 'active' => ['stockpiles.dashboard'], 'perm' => 'stockpile.view', 'icon' => 'warehouse'],
            ['label' => 'Stockpile', 'route' => 'stockpiles.index', 'active' => ['stockpiles.index', 'stockpiles.create', 'stockpiles.store', 'stockpiles.show', 'stockpiles.survey', 'stockpile-surveys.*'], 'perm' => 'stockpile.view', 'icon' => 'layers'],
            ['label' => 'Biaya Tambang', 'route' => 'cost.dashboard', 'active' => ['cost.dashboard', 'cost.others.*'], 'perm' => 'cost.view', 'icon' => 'chart'],
        ]],
        ['group' => 'Armada & BBM', 'icon' => 'truck', 'items' => [
            ['label' => 'Dashboard Armada', 'route' => 'fleet.dashboard', 'active' => ['fleet.dashboard', 'fleet.availability', 'fleet.utilization', 'fleet.downtime', 'fleet.cost'], 'perm' => 'fleet.view', 'icon' => 'dashboard'],
            ['label' => 'Kendaraan', 'route' => 'vehicles.index', 'active' => 'vehicles.*', 'perm' => 'fleet.view', 'icon' => 'truck'],
            ['label' => 'Kategori Alat', 'route' => 'equipment-categories.index', 'active' => 'equipment-categories.*', 'perm' => 'fleet.view', 'icon' => 'layers'],
            ['label' => 'HM / Odometer', 'route' => 'fleet.meters', 'active' => 'fleet.meters*', 'perm' => 'fleet.view', 'icon' => 'clock'],
            ['label' => 'Inspeksi Unit', 'route' => 'fleet.inspections', 'active' => 'fleet.inspections*', 'perm' => 'fleet.view', 'icon' => 'eye'],
            ['label' => 'Assignment Operator', 'route' => 'fleet.assignments', 'active' => 'fleet.assignments*', 'perm' => 'fleet.view', 'icon' => 'users'],
            ['label' => 'Ban', 'route' => 'tires.index', 'active' => 'tires.*', 'perm' => 'tire.view', 'icon' => 'tire'],
            ['label' => 'Dashboard BBM', 'route' => 'fuel.dashboard', 'active' => ['fuel.dashboard', 'fuel.stock', 'fuel.consumption', 'fuel.variance'], 'perm' => 'fuel.view', 'icon' => 'fuel'],
            ['label' => 'Tangki BBM', 'route' => 'fuel-tanks.index', 'active' => 'fuel-tanks.*', 'perm' => 'fuel.view', 'icon' => 'box'],
            ['label' => 'Penerimaan BBM', 'route' => 'fuel-receipts.index', 'active' => 'fuel-receipts.*', 'perm' => 'fuel.view', 'icon' => 'download'],
            ['label' => 'Issue BBM', 'route' => 'fuel-issues.index', 'active' => 'fuel-issues.*', 'perm' => 'fuel.view', 'icon' => 'upload'],
            ['label' => 'Transfer BBM', 'route' => 'fuel-transfers.index', 'active' => 'fuel-transfers.*', 'perm' => 'fuel.view', 'icon' => 'arrow-right'],
            ['label' => 'Dip Tangki', 'route' => 'fuel-dips.index', 'active' => 'fuel-dips.*', 'perm' => 'fuel.view', 'icon' => 'eye'],
            ['label' => 'Telematics', 'route' => 'telematics.index', 'active' => 'telematics.*', 'perm' => 'telematics.view', 'icon' => 'radio'],
        ]],
        ['group' => 'Quality & Lab', 'icon' => 'flask', 'items' => [
            ['label' => 'Parameter Uji', 'route' => 'quality-parameters.index', 'active' => 'quality-parameters.*', 'perm' => 'quality.view', 'icon' => 'sliders'],
            ['label' => 'Spesifikasi Produk', 'route' => 'specs.index', 'active' => 'specs.*', 'perm' => 'quality.view', 'icon' => 'file'],
            ['label' => 'Sampel QC', 'route' => 'samples.index', 'active' => 'samples.*', 'perm' => 'quality.view', 'icon' => 'flask'],
            ['label' => 'Quality Hold', 'route' => 'quality-holds.index', 'active' => 'quality-holds.*', 'perm' => 'quality.view', 'icon' => 'alert'],
        ]],
        ['group' => 'Inventory & Pengadaan', 'icon' => 'box', 'items' => [
            ['label' => 'Master Item', 'route' => 'items.index', 'active' => 'items.*', 'perm' => 'inventory.view', 'icon' => 'box'],
            ['label' => 'Gudang & Stockpile', 'route' => 'warehouses.index', 'active' => 'warehouses.*', 'perm' => 'inventory.view', 'icon' => 'warehouse'],
            ['label' => 'Saldo Stok', 'route' => 'stock.balance', 'active' => 'stock.balance', 'perm' => 'stock.view', 'icon' => 'chart'],
            ['label' => 'Kartu Stok', 'route' => 'stock.card', 'active' => 'stock.card', 'perm' => 'stock.view', 'icon' => 'book'],
            ['label' => 'Transfer Stok', 'route' => 'stock-transfers.index', 'active' => 'stock-transfers.*', 'perm' => 'stock.view', 'icon' => 'arrow-right'],
            ['label' => 'Penyesuaian Stok', 'route' => 'stock-adjustments.index', 'active' => 'stock-adjustments.*', 'perm' => 'stock.view', 'icon' => 'sliders'],
            ['label' => 'Permintaan Beli', 'route' => 'purchase-requests.index', 'active' => 'purchase-requests.*', 'perm' => 'purchase_request.view', 'icon' => 'file'],
            ['label' => 'Order Pembelian', 'route' => 'purchase-orders.index', 'active' => 'purchase-orders.*', 'perm' => 'purchase_order.view', 'icon' => 'cart'],
            ['label' => 'Penerimaan Barang', 'route' => 'goods-receipts.index', 'active' => 'goods-receipts.*', 'perm' => 'goods_receipt.view', 'icon' => 'download'],
            ['label' => 'Tagihan Vendor', 'route' => 'vendor-bills.index', 'active' => 'vendor-bills.*', 'perm' => 'vendor_bill.view', 'icon' => 'money'],
        ]],
        ['group' => 'Penjualan', 'icon' => 'money', 'items' => [
            ['label' => 'Customer', 'route' => 'customers.index', 'active' => 'customers.*', 'perm' => 'sales.view', 'icon' => 'users'],
            ['label' => 'Order Penjualan', 'route' => 'sales-orders.index', 'active' => 'sales-orders.*', 'perm' => 'sales_order.view', 'icon' => 'cart'],
            ['label' => 'Surat Jalan', 'route' => 'delivery-orders.index', 'active' => 'delivery-orders.*', 'perm' => 'delivery_order.view', 'icon' => 'file'],
            ['label' => 'Faktur', 'route' => 'invoices.index', 'active' => 'invoices.*', 'perm' => 'invoice.view', 'icon' => 'money'],
            ['label' => 'Pembayaran', 'route' => 'payments.index', 'active' => 'payments.*', 'perm' => 'payment.view', 'icon' => 'coin'],
            ['label' => 'Deposit Customer', 'route' => 'deposit.index', 'active' => 'deposit.*', 'perm' => 'deposit.view', 'icon' => 'wallet'],
            ['label' => 'Daftar Harga', 'route' => 'price-lists.index', 'active' => 'price-lists.*', 'perm' => 'price.view', 'icon' => 'tag'],
            ['label' => 'Selisih Harga', 'route' => 'price_variance.index', 'active' => 'price_variance.*', 'perm' => 'price_variance.view', 'icon' => 'chart'],
            ['label' => 'Kontrak Customer', 'route' => 'customer-contracts.index', 'active' => 'customer-contracts.*', 'perm' => 'contract.view', 'icon' => 'book'],
            ['label' => 'Kontrak Supplier', 'route' => 'supplier-contracts.index', 'active' => 'supplier-contracts.*', 'perm' => 'contract.view', 'icon' => 'book'],
            ['label' => 'Kontrak Hauling', 'route' => 'hauling-contracts.index', 'active' => 'hauling-contracts.*', 'perm' => 'contract.view', 'icon' => 'truck'],
        ]],
        ['group' => 'Aset & Pemeliharaan', 'icon' => 'wrench', 'items' => [
            ['label' => 'Aset', 'route' => 'assets.index', 'active' => 'assets.*', 'perm' => 'asset.view', 'icon' => 'box'],
            ['label' => 'Peralatan', 'route' => 'equipment.index', 'active' => 'equipment.*', 'perm' => 'asset.view', 'icon' => 'wrench'],
            ['label' => 'Work Order', 'route' => 'work-orders.index', 'active' => 'work-orders.*', 'perm' => 'work_order.view', 'icon' => 'file'],
            ['label' => 'Jadwal Pemeliharaan', 'route' => 'maintenance-schedules.index', 'active' => 'maintenance-schedules.*', 'perm' => 'maintenance.view', 'icon' => 'calendar'],
        ]],
        ['group' => 'Keuangan & Akuntansi', 'icon' => 'bank', 'items' => [
            ['label' => 'Bagan Akun', 'route' => 'coa.index', 'active' => 'coa.*', 'perm' => 'finance.view', 'icon' => 'book'],
            ['label' => 'Jurnal', 'route' => 'journals.index', 'active' => 'journals.*', 'perm' => 'journal.view', 'icon' => 'file'],
            ['label' => 'Kas & Bank', 'route' => 'cash-accounts.index', 'active' => 'cash-accounts.*', 'perm' => 'finance.view', 'icon' => 'bank'],
            ['label' => 'Neraca Saldo', 'route' => 'finance.trial_balance', 'active' => 'finance.trial_balance', 'perm' => 'ledger.view', 'icon' => 'chart'],
            ['label' => 'Buku Besar', 'route' => 'finance.ledger', 'active' => 'finance.ledger', 'perm' => 'ledger.view', 'icon' => 'book-open'],
            ['label' => 'Laba Rugi', 'route' => 'finance.pl', 'active' => 'finance.pl', 'perm' => 'ledger.view', 'icon' => 'chart'],
            ['label' => 'Neraca', 'route' => 'finance.balance_sheet', 'active' => 'finance.balance_sheet', 'perm' => 'ledger.view', 'icon' => 'layers'],
            ['label' => 'Umur Piutang', 'route' => 'finance.ar_aging', 'active' => 'finance.ar_aging', 'perm' => 'ledger.view', 'icon' => 'clock'],
            ['label' => 'Umur Utang', 'route' => 'finance.ap_aging', 'active' => 'finance.ap_aging', 'perm' => 'ledger.view', 'icon' => 'clock'],
            ['label' => 'Arus Kas', 'route' => 'finance.cash_flow', 'active' => 'finance.cash_flow', 'perm' => 'ledger.view', 'icon' => 'coin'],
            ['label' => 'Pajak', 'route' => 'tax.index', 'active' => 'tax.*', 'perm' => 'tax.view', 'icon' => 'money'],
            ['label' => 'Budget', 'route' => 'budgets.index', 'active' => 'budgets.*', 'perm' => 'budget.view', 'icon' => 'wallet'],
            ['label' => 'Periode Fiskal', 'route' => 'fiscal-periods.index', 'active' => 'fiscal-periods.*', 'perm' => 'fiscal.view', 'icon' => 'calendar'],
        ]],
        ['group' => 'HSE & Compliance', 'icon' => 'heart', 'items' => [
            ['label' => 'Dashboard HSE', 'route' => 'hse.dashboard', 'active' => ['hse.dashboard'], 'perm' => 'hse.view', 'icon' => 'dashboard'],
            ['label' => 'Laporan Insiden', 'route' => 'hse.reports.index', 'active' => ['hse.reports.*', 'hse.actions.*'], 'perm' => 'hse.view', 'icon' => 'alert'],
            ['label' => 'Permit Kerja', 'route' => 'hse.permits.index', 'active' => 'hse.permits.*', 'perm' => 'hse.view', 'icon' => 'shield'],
            ['label' => 'Kegiatan K3', 'route' => 'hse.activities.index', 'active' => 'hse.activities.*', 'perm' => 'hse.view', 'icon' => 'users'],
            ['label' => 'Compliance Register', 'route' => 'compliance.index', 'active' => ['compliance.index', 'compliance.create', 'compliance.store', 'compliance.show'], 'perm' => 'compliance.view', 'icon' => 'check-circle'],
            ['label' => 'Kalender Compliance', 'route' => 'compliance.calendar', 'active' => ['compliance.calendar'], 'perm' => 'compliance.view', 'icon' => 'calendar'],
        ]],
        ['group' => 'Dokumen & CSR', 'icon' => 'file', 'items' => [
            ['label' => 'Manajemen Dokumen', 'route' => 'documents.index', 'active' => 'documents.*', 'perm' => 'document.view', 'icon' => 'file'],
            ['label' => 'Program CSR', 'route' => 'csr.index', 'active' => 'csr.*', 'perm' => 'csr.view', 'icon' => 'heart'],
        ]],
        ['group' => 'Laporan', 'icon' => 'chart', 'items' => [
            ['label' => 'Executive Dashboard', 'route' => 'executive.index', 'active' => 'executive.*', 'perm' => 'executive.view', 'icon' => 'dashboard'],
            ['label' => 'Forecast & Anomali', 'route' => 'forecast.index', 'active' => 'forecast.*', 'perm' => 'forecast.view', 'icon' => 'chart'],
            ['label' => 'AI Copilot', 'route' => 'ai.index', 'active' => 'ai.*', 'perm' => 'ai.view', 'icon' => 'cpu'],
            ['label' => 'Produksi Tambang', 'route' => 'report.mining', 'active' => 'report.mining', 'perm' => 'report.view', 'icon' => 'pickaxe'],
            ['label' => 'Armada & Utilisasi', 'route' => 'report.fleet', 'active' => 'report.fleet', 'perm' => 'report.view', 'icon' => 'truck'],
            ['label' => 'BBM & Variansi', 'route' => 'report.fuel', 'active' => 'report.fuel', 'perm' => 'report.view', 'icon' => 'fuel'],
            ['label' => 'Ban & Lifetime', 'route' => 'report.tire', 'active' => 'report.tire', 'perm' => 'report.view', 'icon' => 'tire'],
            ['label' => 'Dispatch & Cycle', 'route' => 'report.dispatch', 'active' => 'report.dispatch', 'perm' => 'report.view', 'icon' => 'flag'],
            ['label' => 'Stockpile & Rekonsiliasi', 'route' => 'report.stockpile', 'active' => 'report.stockpile', 'perm' => 'report.view', 'icon' => 'warehouse'],
            ['label' => 'Quality', 'route' => 'report.quality', 'active' => 'report.quality', 'perm' => 'report.view', 'icon' => 'flask'],
            ['label' => 'Kontrak & Realisasi', 'route' => 'report.contract', 'active' => 'report.contract', 'perm' => 'report.view', 'icon' => 'book'],
            ['label' => 'Budget vs Aktual', 'route' => 'report.budget', 'active' => 'report.budget', 'perm' => 'report.view', 'icon' => 'wallet'],
            ['label' => 'Inventory', 'route' => 'report.inventory', 'active' => 'report.inventory', 'perm' => 'report.view', 'icon' => 'box'],
            ['label' => 'Penjualan', 'route' => 'report.sales', 'active' => 'report.sales', 'perm' => 'report.view', 'icon' => 'money'],
            ['label' => 'HR', 'route' => 'report.hr', 'active' => 'report.hr', 'perm' => 'report.view', 'icon' => 'user'],
            ['label' => 'Pemeliharaan', 'route' => 'report.maintenance', 'active' => 'report.maintenance', 'perm' => 'report.view', 'icon' => 'wrench'],
        ]],
        ['group' => 'Persetujuan', 'icon' => 'check-circle', 'items' => [
            ['label' => 'Persetujuan Saya', 'route' => 'approval.index', 'active' => 'approval.*', 'perm' => 'approval.view', 'icon' => 'check-circle'],
        ]],
    ];

    $isMenuActive = function ($active) {
        foreach ((array) $active as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };
    $isGroupActive = function ($items) use ($isMenuActive) {
        foreach ($items as $sub) {
            if ($isMenuActive($sub['active'])) {
                return true;
            }
        }
        return false;
    };
@endphp
<div id="sbOverlay" class="fixed inset-0 z-20 bg-slate-900/50 hidden lg:hidden"></div>
<aside id="sidebar" aria-label="Navigasi utama"
    class="fixed inset-y-0 left-0 z-30 flex flex-col bg-navy-900 text-slate-300 transition-[width,transform] duration-200 w-64 -translate-x-full lg:translate-x-0 dark:bg-navy-950">
    <div class="h-16 flex items-center gap-3 px-4 border-b border-white/10 shrink-0">
        <div class="w-9 h-9 flex-none rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-900">M</div>
        <div class="sb-label min-w-0">
            <div class="font-bold text-white text-sm tracking-wide truncate">MINING ERP</div>
            <div class="text-[10px] text-slate-500">Integrated Operations</div>
        </div>
        <button type="button" id="sbCollapse" title="Ciutkan sidebar" aria-label="Ciutkan atau bentangkan sidebar"
            class="sb-label ml-auto hidden lg:flex p-1.5 rounded-md text-slate-500 hover:text-white hover:bg-white/10">
            <x-ui.icon name="menu" class="w-4 h-4" />
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto nice-scroll py-3 px-2 space-y-1 text-[13px]" aria-label="Menu modul">
        @foreach ($sidebarMenu as $item)
            @if (isset($item['group']))
                @php $visibleItems = collect($item['items'])->filter(fn ($i) => auth()->user()->hasPermission($i['perm'])); @endphp
                @if ($visibleItems->isNotEmpty())
                    <div x-data="{ open: {{ $isGroupActive($visibleItems) ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" aria-expanded="false"
                            class="sb-groupbtn w-full flex items-center gap-2.5 px-3 py-2 rounded-md hover:bg-white/5 text-slate-400 uppercase text-[11px] tracking-wider font-semibold">
                            <x-ui.icon :name="$item['icon'] ?? 'box'" class="w-4 h-4 flex-none sb-ic" />
                            <span class="sb-label flex-1 text-left truncate">{{ $item['group'] }}</span>
                            <svg class="sb-label w-3 h-3 flex-none transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="sb-sub mt-0.5 space-y-0.5">
                            @foreach ($visibleItems as $sub)
                                @php $subActive = $isMenuActive($sub['active']); @endphp
                                <a href="{{ route($sub['route']) }}" title="{{ $sub['label'] }}" @if($subActive) aria-current="page" @endif
                                   class="group/sb flex items-center gap-2.5 px-3 py-1.5 rounded-md {{ $subActive ? 'bg-white/10 text-white font-medium' : 'hover:bg-white/5 hover:text-white' }}">
                                    <x-ui.icon :name="$sub['icon'] ?? 'box'" class="w-4 h-4 flex-none {{ $subActive ? 'text-amber-400' : 'text-slate-500 group-hover/sb:text-slate-300' }}" />
                                    <span class="sb-label truncate">{{ $sub['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                @if (auth()->user()->hasPermission($item['perm']))
                    @php $itemActive = $isMenuActive($item['active']); @endphp
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}" @if($itemActive) aria-current="page" @endif
                       class="flex items-center gap-3 px-3 py-2 rounded-md {{ $itemActive ? 'bg-white/10 text-white font-medium' : 'hover:bg-white/5 hover:text-white' }}">
                        <x-ui.icon :name="$item['icon']" class="w-[18px] h-[18px] flex-none {{ $itemActive ? 'text-amber-400' : 'text-slate-500' }}" />
                        <span class="sb-label">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endif
        @endforeach
    </nav>
    <div class="p-3 border-t border-white/10 text-[11px] text-slate-500 sb-label">v1.0 · Mining ERP</div>
</aside>
<script>
(function () {
    var sb = document.getElementById('sidebar'), ov = document.getElementById('sbOverlay');
    function closeMobile() { if (window.innerWidth < 1024) { sb.classList.add('-translate-x-full'); ov.classList.add('hidden'); } }
    window.toggleSidebar = function () {
        if (window.innerWidth < 1024) {
            var hidden = sb.classList.toggle('-translate-x-full');
            ov.classList.toggle('hidden', hidden);
        } else {
            var c = document.documentElement.classList.toggle('sb-collapsed');
            try { localStorage.setItem('sb-collapsed', c ? '1' : '0'); } catch (e) {}
        }
    };
    ov.addEventListener('click', closeMobile);
    document.getElementById('sbCollapse').addEventListener('click', window.toggleSidebar);
})();
</script>
