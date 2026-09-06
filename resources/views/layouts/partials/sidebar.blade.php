@php
    // Struktur menu: label / route (nama route aktual) / active (pola wildcard) / perm / icon (opsional).
    // TIDAK ada fallback '#': route() dipanggil langsung agar nama route yang salah
    // langsung error saat development (dijaga oleh SidebarRouteTest).
    $sidebarMenu = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10', 'perm' => 'dashboard.view'],
        ['group' => 'Administrasi', 'items' => [
            ['label' => 'Manajemen Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'perm' => 'user.view'],
            ['label' => 'Manajemen Peran', 'route' => 'role.index', 'active' => 'role.*', 'perm' => 'role.view'],
            ['label' => 'Audit Trail', 'route' => 'audit.index', 'active' => 'audit.*', 'perm' => 'audit.view'],
            ['label' => 'Pengaturan Sistem', 'route' => 'setting.index', 'active' => 'setting.*', 'perm' => 'setting.view'],
        ]],
        ['group' => 'Organisasi', 'items' => [
            ['label' => 'Perusahaan', 'route' => 'companies.index', 'active' => 'companies.*', 'perm' => 'company.view'],
            ['label' => 'Cabang', 'route' => 'branches.index', 'active' => 'branches.*', 'perm' => 'branch.view'],
            ['label' => 'Site Tambang', 'route' => 'sites.index', 'active' => 'sites.*', 'perm' => 'site.view'],
            ['label' => 'Divisi & Departemen', 'route' => 'divisions.index', 'active' => ['divisions.*', 'departments.*'], 'perm' => 'division.view'],
        ]],
        ['group' => 'HR & Payroll', 'items' => [
            ['label' => 'Karyawan', 'route' => 'employees.index', 'active' => 'employees.*', 'perm' => 'employee.view'],
            ['label' => 'Absensi', 'route' => 'attendances.index', 'active' => 'attendances.*', 'perm' => 'attendance.view'],
            ['label' => 'Cuti', 'route' => 'leaves.index', 'active' => 'leaves.*', 'perm' => 'leave.view'],
            ['label' => 'Lembur', 'route' => 'overtimes.index', 'active' => 'overtimes.*', 'perm' => 'overtime.view'],
            ['label' => 'Payroll', 'route' => 'payroll-runs.index', 'active' => ['payroll-runs.*', 'payroll.*'], 'perm' => 'payroll.view'],
            ['label' => 'Insentif Operator', 'route' => 'operator-incentives.index', 'active' => 'operator-incentives.*', 'perm' => 'incentive.view'],
        ]],
        ['group' => 'Operasi Tambang', 'items' => [
            ['label' => 'Aktivitas Tambang', 'route' => 'mining-activities.index', 'active' => ['mining-activities.*', 'mining.*'], 'perm' => 'mining.view'],
            ['label' => 'Timbangan', 'route' => 'weighbridge-tickets.index', 'active' => ['weighbridge-tickets.*', 'weighbridge.*'], 'perm' => 'weighbridge.view'],
            ['label' => 'Perangkat Timbangan', 'route' => 'weighbridge.devices.index', 'active' => 'weighbridge.devices.*', 'perm' => 'weighbridge.view'],
            ['label' => 'Produksi Crusher', 'route' => 'production-batches.index', 'active' => ['production-batches.*', 'production.*'], 'perm' => 'production.view'],
            ['label' => 'Dispatch Board', 'route' => 'dispatch.dashboard', 'active' => ['dispatch.dashboard'], 'perm' => 'dispatch.view'],
            ['label' => 'Trip Dispatch', 'route' => 'dispatch.trips.index', 'active' => ['dispatch.trips.*'], 'perm' => 'dispatch.view'],
            ['label' => 'Titik Muat', 'route' => 'loading-points.index', 'active' => 'loading-points.*', 'perm' => 'dispatch.view'],
            ['label' => 'Titik Bongkar', 'route' => 'dumping-points.index', 'active' => 'dumping-points.*', 'perm' => 'dispatch.view'],
            ['label' => 'Rute Hauling', 'route' => 'hauling-routes.index', 'active' => 'hauling-routes.*', 'perm' => 'dispatch.view'],
            ['label' => 'Stockpile Board', 'route' => 'stockpiles.dashboard', 'active' => ['stockpiles.dashboard'], 'perm' => 'stockpile.view'],
            ['label' => 'Stockpile', 'route' => 'stockpiles.index', 'active' => ['stockpiles.index', 'stockpiles.create', 'stockpiles.store', 'stockpiles.show', 'stockpiles.survey', 'stockpile-surveys.*'], 'perm' => 'stockpile.view'],
            ['label' => 'Biaya Tambang', 'route' => 'cost.dashboard', 'active' => ['cost.dashboard', 'cost.others.*'], 'perm' => 'cost.view'],
        ]],
        ['group' => 'Armada & BBM', 'items' => [
            ['label' => 'Dashboard Armada', 'route' => 'fleet.dashboard', 'active' => ['fleet.dashboard', 'fleet.availability', 'fleet.utilization', 'fleet.downtime', 'fleet.cost'], 'perm' => 'fleet.view'],
            ['label' => 'Kendaraan', 'route' => 'vehicles.index', 'active' => 'vehicles.*', 'perm' => 'fleet.view'],
            ['label' => 'Kategori Alat', 'route' => 'equipment-categories.index', 'active' => 'equipment-categories.*', 'perm' => 'fleet.view'],
            ['label' => 'HM / Odometer', 'route' => 'fleet.meters', 'active' => 'fleet.meters*', 'perm' => 'fleet.view'],
            ['label' => 'Inspeksi Unit', 'route' => 'fleet.inspections', 'active' => 'fleet.inspections*', 'perm' => 'fleet.view'],
            ['label' => 'Assignment Operator', 'route' => 'fleet.assignments', 'active' => 'fleet.assignments*', 'perm' => 'fleet.view'],
            ['label' => 'Ban', 'route' => 'tires.index', 'active' => 'tires.*', 'perm' => 'tire.view'],
            ['label' => 'Dashboard BBM', 'route' => 'fuel.dashboard', 'active' => ['fuel.dashboard', 'fuel.stock', 'fuel.consumption', 'fuel.variance'], 'perm' => 'fuel.view'],
            ['label' => 'Tangki BBM', 'route' => 'fuel-tanks.index', 'active' => 'fuel-tanks.*', 'perm' => 'fuel.view'],
            ['label' => 'Penerimaan BBM', 'route' => 'fuel-receipts.index', 'active' => 'fuel-receipts.*', 'perm' => 'fuel.view'],
            ['label' => 'Issue BBM', 'route' => 'fuel-issues.index', 'active' => 'fuel-issues.*', 'perm' => 'fuel.view'],
            ['label' => 'Transfer BBM', 'route' => 'fuel-transfers.index', 'active' => 'fuel-transfers.*', 'perm' => 'fuel.view'],
            ['label' => 'Dip Tangki', 'route' => 'fuel-dips.index', 'active' => 'fuel-dips.*', 'perm' => 'fuel.view'],
            ['label' => 'Telematics', 'route' => 'telematics.index', 'active' => 'telematics.*', 'perm' => 'telematics.view'],
        ]],
        ['group' => 'Quality & Lab', 'items' => [
            ['label' => 'Parameter Uji', 'route' => 'quality-parameters.index', 'active' => 'quality-parameters.*', 'perm' => 'quality.view'],
            ['label' => 'Spesifikasi Produk', 'route' => 'specs.index', 'active' => 'specs.*', 'perm' => 'quality.view'],
            ['label' => 'Sampel QC', 'route' => 'samples.index', 'active' => 'samples.*', 'perm' => 'quality.view'],
            ['label' => 'Quality Hold', 'route' => 'quality-holds.index', 'active' => 'quality-holds.*', 'perm' => 'quality.view'],
        ]],
        ['group' => 'Inventory & Pengadaan', 'items' => [
            ['label' => 'Master Item', 'route' => 'items.index', 'active' => 'items.*', 'perm' => 'inventory.view'],
            ['label' => 'Gudang & Stockpile', 'route' => 'warehouses.index', 'active' => 'warehouses.*', 'perm' => 'inventory.view'],
            ['label' => 'Saldo Stok', 'route' => 'stock.balance', 'active' => 'stock.balance', 'perm' => 'stock.view'],
            ['label' => 'Kartu Stok', 'route' => 'stock.card', 'active' => 'stock.card', 'perm' => 'stock.view'],
            ['label' => 'Transfer Stok', 'route' => 'stock-transfers.index', 'active' => 'stock-transfers.*', 'perm' => 'stock.view'],
            ['label' => 'Penyesuaian Stok', 'route' => 'stock-adjustments.index', 'active' => 'stock-adjustments.*', 'perm' => 'stock.view'],
            ['label' => 'Permintaan Beli', 'route' => 'purchase-requests.index', 'active' => 'purchase-requests.*', 'perm' => 'purchase_request.view'],
            ['label' => 'Order Pembelian', 'route' => 'purchase-orders.index', 'active' => 'purchase-orders.*', 'perm' => 'purchase_order.view'],
            ['label' => 'Penerimaan Barang', 'route' => 'goods-receipts.index', 'active' => 'goods-receipts.*', 'perm' => 'goods_receipt.view'],
            ['label' => 'Tagihan Vendor', 'route' => 'vendor-bills.index', 'active' => 'vendor-bills.*', 'perm' => 'vendor_bill.view'],
        ]],
        ['group' => 'Penjualan', 'items' => [
            ['label' => 'Customer', 'route' => 'customers.index', 'active' => 'customers.*', 'perm' => 'sales.view'],
            ['label' => 'Order Penjualan', 'route' => 'sales-orders.index', 'active' => 'sales-orders.*', 'perm' => 'sales_order.view'],
            ['label' => 'Surat Jalan', 'route' => 'delivery-orders.index', 'active' => 'delivery-orders.*', 'perm' => 'delivery_order.view'],
            ['label' => 'Faktur', 'route' => 'invoices.index', 'active' => 'invoices.*', 'perm' => 'invoice.view'],
            ['label' => 'Pembayaran', 'route' => 'payments.index', 'active' => 'payments.*', 'perm' => 'payment.view'],
            ['label' => 'Deposit Customer', 'route' => 'deposit.index', 'active' => 'deposit.*', 'perm' => 'deposit.view'],
            ['label' => 'Daftar Harga', 'route' => 'price-lists.index', 'active' => 'price-lists.*', 'perm' => 'price.view'],
            ['label' => 'Selisih Harga', 'route' => 'price_variance.index', 'active' => 'price_variance.*', 'perm' => 'price_variance.view'],
            ['label' => 'Kontrak Customer', 'route' => 'customer-contracts.index', 'active' => 'customer-contracts.*', 'perm' => 'contract.view'],
            ['label' => 'Kontrak Supplier', 'route' => 'supplier-contracts.index', 'active' => 'supplier-contracts.*', 'perm' => 'contract.view'],
            ['label' => 'Kontrak Hauling', 'route' => 'hauling-contracts.index', 'active' => 'hauling-contracts.*', 'perm' => 'contract.view'],
        ]],
        ['group' => 'Aset & Pemeliharaan', 'items' => [
            ['label' => 'Aset', 'route' => 'assets.index', 'active' => 'assets.*', 'perm' => 'asset.view'],
            ['label' => 'Peralatan', 'route' => 'equipment.index', 'active' => 'equipment.*', 'perm' => 'asset.view'],
            ['label' => 'Work Order', 'route' => 'work-orders.index', 'active' => 'work-orders.*', 'perm' => 'work_order.view'],
            ['label' => 'Jadwal Pemeliharaan', 'route' => 'maintenance-schedules.index', 'active' => 'maintenance-schedules.*', 'perm' => 'maintenance.view'],
        ]],
        ['group' => 'Keuangan & Akuntansi', 'items' => [
            ['label' => 'Bagan Akun', 'route' => 'coa.index', 'active' => 'coa.*', 'perm' => 'finance.view'],
            ['label' => 'Jurnal', 'route' => 'journals.index', 'active' => 'journals.*', 'perm' => 'journal.view'],
            ['label' => 'Kas & Bank', 'route' => 'cash-accounts.index', 'active' => 'cash-accounts.*', 'perm' => 'finance.view'],
            ['label' => 'Neraca Saldo', 'route' => 'finance.trial_balance', 'active' => 'finance.trial_balance', 'perm' => 'ledger.view'],
            ['label' => 'Buku Besar', 'route' => 'finance.ledger', 'active' => 'finance.ledger', 'perm' => 'ledger.view'],
            ['label' => 'Laba Rugi', 'route' => 'finance.pl', 'active' => 'finance.pl', 'perm' => 'ledger.view'],
            ['label' => 'Neraca', 'route' => 'finance.balance_sheet', 'active' => 'finance.balance_sheet', 'perm' => 'ledger.view'],
            ['label' => 'Umur Piutang', 'route' => 'finance.ar_aging', 'active' => 'finance.ar_aging', 'perm' => 'ledger.view'],
            ['label' => 'Umur Utang', 'route' => 'finance.ap_aging', 'active' => 'finance.ap_aging', 'perm' => 'ledger.view'],
            ['label' => 'Arus Kas', 'route' => 'finance.cash_flow', 'active' => 'finance.cash_flow', 'perm' => 'ledger.view'],
            ['label' => 'Pajak', 'route' => 'tax.index', 'active' => 'tax.*', 'perm' => 'tax.view'],
            ['label' => 'Budget', 'route' => 'budgets.index', 'active' => 'budgets.*', 'perm' => 'budget.view'],
            ['label' => 'Periode Fiskal', 'route' => 'fiscal-periods.index', 'active' => 'fiscal-periods.*', 'perm' => 'fiscal.view'],
        ]],
        ['group' => 'HSE & Compliance', 'items' => [
            ['label' => 'Dashboard HSE', 'route' => 'hse.dashboard', 'active' => ['hse.dashboard'], 'perm' => 'hse.view'],
            ['label' => 'Laporan Insiden', 'route' => 'hse.reports.index', 'active' => ['hse.reports.*', 'hse.actions.*'], 'perm' => 'hse.view'],
            ['label' => 'Permit Kerja', 'route' => 'hse.permits.index', 'active' => 'hse.permits.*', 'perm' => 'hse.view'],
            ['label' => 'Kegiatan K3', 'route' => 'hse.activities.index', 'active' => 'hse.activities.*', 'perm' => 'hse.view'],
            ['label' => 'Compliance Register', 'route' => 'compliance.index', 'active' => ['compliance.index', 'compliance.create', 'compliance.store', 'compliance.show'], 'perm' => 'compliance.view'],
            ['label' => 'Kalender Compliance', 'route' => 'compliance.calendar', 'active' => ['compliance.calendar'], 'perm' => 'compliance.view'],
        ]],
        ['group' => 'Dokumen & CSR', 'items' => [
            ['label' => 'Manajemen Dokumen', 'route' => 'documents.index', 'active' => 'documents.*', 'perm' => 'document.view'],
            ['label' => 'Program CSR', 'route' => 'csr.index', 'active' => 'csr.*', 'perm' => 'csr.view'],
        ]],
        ['group' => 'Laporan', 'items' => [
            ['label' => 'Executive Dashboard', 'route' => 'executive.index', 'active' => 'executive.*', 'perm' => 'executive.view'],
            ['label' => 'Forecast & Anomali', 'route' => 'forecast.index', 'active' => 'forecast.*', 'perm' => 'forecast.view'],
            ['label' => 'AI Copilot', 'route' => 'ai.index', 'active' => 'ai.*', 'perm' => 'ai.view'],
            ['label' => 'Produksi Tambang', 'route' => 'report.mining', 'active' => 'report.mining', 'perm' => 'report.view'],
            ['label' => 'Inventory', 'route' => 'report.inventory', 'active' => 'report.inventory', 'perm' => 'report.view'],
            ['label' => 'Penjualan', 'route' => 'report.sales', 'active' => 'report.sales', 'perm' => 'report.view'],
            ['label' => 'HR', 'route' => 'report.hr', 'active' => 'report.hr', 'perm' => 'report.view'],
            ['label' => 'Pemeliharaan', 'route' => 'report.maintenance', 'active' => 'report.maintenance', 'perm' => 'report.view'],
        ]],
        ['group' => 'Persetujuan', 'items' => [
            ['label' => 'Persetujuan Saya', 'route' => 'approval.index', 'active' => 'approval.*', 'perm' => 'approval.view'],
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
<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 -translate-x-full lg:translate-x-0 transition-transform duration-200 bg-slate-900 text-slate-300 flex flex-col">
    <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800 shrink-0">
        <div class="w-9 h-9 rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-900">M</div>
        <div>
            <div class="font-bold text-white text-sm tracking-wide">MINING ERP</div>
            <div class="text-[10px] text-slate-500">Integrated Operations</div>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-1 text-[13px]">
        @foreach ($sidebarMenu as $item)
            @if (isset($item['group']))
                @php
                    $visibleItems = collect($item['items'])->filter(fn ($i) => auth()->user()->hasPermission($i['perm']));
                @endphp
                @if ($visibleItems->isNotEmpty())
                    <div x-data="{ open: {{ $isGroupActive($visibleItems) ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-slate-800 text-slate-400 uppercase text-[11px] tracking-wider font-semibold">
                            {{ $item['group'] }}
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-0.5">
                            @foreach ($visibleItems as $sub)
                                @php $subActive = $isMenuActive($sub['active']); @endphp
                                <a href="{{ route($sub['route']) }}"
                                   class="flex items-center px-3 py-1.5 rounded-md {{ $subActive ? 'bg-slate-800 text-white font-medium' : 'hover:bg-slate-800/60 hover:text-white' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-600 mr-2.5 {{ $subActive ? 'bg-amber-500' : '' }}"></span>
                                    {{ $sub['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                @if (auth()->user()->hasPermission($item['perm']))
                    @php $itemActive = $isMenuActive($item['active']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md {{ $itemActive ? 'bg-slate-800 text-white font-medium' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4 {{ $itemActive ? 'text-amber-500' : 'text-slate-500' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endif
            @endif
        @endforeach
    </nav>
    <div class="p-4 border-t border-slate-800 text-[11px] text-slate-500">
        v1.0 · Mining ERP
    </div>
</aside>
