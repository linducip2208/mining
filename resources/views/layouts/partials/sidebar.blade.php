@php
    $sidebarMenu = [
        ['code' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10', 'perm' => 'dashboard.view'],
        ['group' => 'Administrasi', 'items' => [
            ['code' => 'user.index', 'label' => 'Manajemen Pengguna', 'route' => 'user.index', 'perm' => 'user.view'],
            ['code' => 'role.index', 'label' => 'Manajemen Peran', 'route' => 'role.index', 'perm' => 'role.view'],
            ['code' => 'audit.index', 'label' => 'Audit Trail', 'route' => 'audit.index', 'perm' => 'audit.view'],
            ['code' => 'setting.index', 'label' => 'Pengaturan Sistem', 'route' => 'setting.index', 'perm' => 'setting.view'],
        ]],
        ['group' => 'Organisasi', 'items' => [
            ['code' => 'company.index', 'label' => 'Perusahaan', 'route' => 'company.index', 'perm' => 'company.view'],
            ['code' => 'branch.index', 'label' => 'Cabang', 'route' => 'branch.index', 'perm' => 'branch.view'],
            ['code' => 'site.index', 'label' => 'Site Tambang', 'route' => 'site.index', 'perm' => 'site.view'],
            ['code' => 'division.index', 'label' => 'Divisi & Departemen', 'route' => 'division.index', 'perm' => 'division.view'],
        ]],
        ['group' => 'HR & Payroll', 'items' => [
            ['code' => 'employee.index', 'label' => 'Karyawan', 'route' => 'employee.index', 'perm' => 'employee.view'],
            ['code' => 'attendance.index', 'label' => 'Absensi', 'route' => 'attendance.index', 'perm' => 'attendance.view'],
            ['code' => 'leave.index', 'label' => 'Cuti', 'route' => 'leave.index', 'perm' => 'leave.view'],
            ['code' => 'overtime.index', 'label' => 'Lembur', 'route' => 'overtime.index', 'perm' => 'overtime.view'],
            ['code' => 'payroll.index', 'label' => 'Payroll', 'route' => 'payroll.index', 'perm' => 'payroll.view'],
            ['code' => 'incentive.index', 'label' => 'Insentif Operator', 'route' => 'incentive.index', 'perm' => 'incentive.view'],
        ]],
        ['group' => 'Operasi Tambang', 'items' => [
            ['code' => 'mining.index', 'label' => 'Aktivitas Tambang', 'route' => 'mining.index', 'perm' => 'mining.view'],
            ['code' => 'weighbridge.index', 'label' => 'Timbangan', 'route' => 'weighbridge.index', 'perm' => 'weighbridge.view'],
            ['code' => 'production.index', 'label' => 'Produksi Crusher', 'route' => 'production.index', 'perm' => 'production.view'],
        ]],
        ['group' => 'Inventory & Pengadaan', 'items' => [
            ['code' => 'item.index', 'label' => 'Master Item', 'route' => 'item.index', 'perm' => 'inventory.view'],
            ['code' => 'warehouse.index', 'label' => 'Gudang & Stockpile', 'route' => 'warehouse.index', 'perm' => 'inventory.view'],
            ['code' => 'stock.balance', 'label' => 'Saldo Stok', 'route' => 'stock.balance', 'perm' => 'stock.view'],
            ['code' => 'stock.card', 'label' => 'Kartu Stok', 'route' => 'stock.card', 'perm' => 'stock.view'],
            ['code' => 'stock.transfer', 'label' => 'Transfer Stok', 'route' => 'stock.transfer.index', 'perm' => 'stock.view'],
            ['code' => 'stock.adjustment', 'label' => 'Penyesuaian Stok', 'route' => 'stock.adjustment.index', 'perm' => 'stock.view'],
            ['code' => 'purchase_request.index', 'label' => 'Permintaan Beli', 'route' => 'purchase_request.index', 'perm' => 'purchase_request.view'],
            ['code' => 'purchase_order.index', 'label' => 'Order Pembelian', 'route' => 'purchase_order.index', 'perm' => 'purchase_order.view'],
            ['code' => 'goods_receipt.index', 'label' => 'Penerimaan Barang', 'route' => 'goods_receipt.index', 'perm' => 'goods_receipt.view'],
            ['code' => 'vendor_bill.index', 'label' => 'Tagihan Vendor', 'route' => 'vendor_bill.index', 'perm' => 'vendor_bill.view'],
        ]],
        ['group' => 'Penjualan', 'items' => [
            ['code' => 'customer.index', 'label' => 'Customer', 'route' => 'customer.index', 'perm' => 'sales.view'],
            ['code' => 'sales_order.index', 'label' => 'Order Penjualan', 'route' => 'sales_order.index', 'perm' => 'sales_order.view'],
            ['code' => 'delivery_order.index', 'label' => 'Surat Jalan', 'route' => 'delivery_order.index', 'perm' => 'delivery_order.view'],
            ['code' => 'invoice.index', 'label' => 'Faktur', 'route' => 'invoice.index', 'perm' => 'invoice.view'],
            ['code' => 'payment.receive', 'label' => 'Pembayaran', 'route' => 'payment.index', 'perm' => 'payment.view'],
            ['code' => 'deposit.index', 'label' => 'Deposit Customer', 'route' => 'deposit.index', 'perm' => 'deposit.view'],
            ['code' => 'price.index', 'label' => 'Daftar Harga', 'route' => 'price.index', 'perm' => 'price.view'],
            ['code' => 'price_variance.index', 'label' => 'Selisih Harga', 'route' => 'price_variance.index', 'perm' => 'price_variance.view'],
        ]],
        ['group' => 'Aset & Pemeliharaan', 'items' => [
            ['code' => 'asset.index', 'label' => 'Aset', 'route' => 'asset.index', 'perm' => 'asset.view'],
            ['code' => 'equipment.index', 'label' => 'Peralatan', 'route' => 'equipment.index', 'perm' => 'asset.view'],
            ['code' => 'work_order.index', 'label' => 'Work Order', 'route' => 'work_order.index', 'perm' => 'work_order.view'],
            ['code' => 'maintenance_schedule.index', 'label' => 'Jadwal Pemeliharaan', 'route' => 'maintenance_schedule.index', 'perm' => 'maintenance.view'],
        ]],
        ['group' => 'Keuangan & Akuntansi', 'items' => [
            ['code' => 'coa.index', 'label' => 'Bagan Akun', 'route' => 'coa.index', 'perm' => 'finance.view'],
            ['code' => 'journal.index', 'label' => 'Jurnal', 'route' => 'journal.index', 'perm' => 'journal.view'],
            ['code' => 'cash_account.index', 'label' => 'Kas & Bank', 'route' => 'cash_account.index', 'perm' => 'finance.view'],
            ['code' => 'finance.trial_balance', 'label' => 'Neraca Saldo', 'route' => 'finance.trial_balance', 'perm' => 'ledger.view'],
            ['code' => 'finance.ledger', 'label' => 'Buku Besar', 'route' => 'finance.ledger', 'perm' => 'ledger.view'],
            ['code' => 'finance.pl', 'label' => 'Laba Rugi', 'route' => 'finance.pl', 'perm' => 'ledger.view'],
            ['code' => 'finance.balance_sheet', 'label' => 'Neraca', 'route' => 'finance.balance_sheet', 'perm' => 'ledger.view'],
            ['code' => 'finance.ar_aging', 'label' => 'Umur Piutang', 'route' => 'finance.ar_aging', 'perm' => 'ledger.view'],
            ['code' => 'finance.ap_aging', 'label' => 'Umur Utang', 'route' => 'finance.ap_aging', 'perm' => 'ledger.view'],
            ['code' => 'finance.cash_flow', 'label' => 'Arus Kas', 'route' => 'finance.cash_flow', 'perm' => 'ledger.view'],
            ['code' => 'tax.index', 'label' => 'Pajak', 'route' => 'tax.index', 'perm' => 'tax.view'],
        ]],
        ['group' => 'Dokumen & CSR', 'items' => [
            ['code' => 'document.index', 'label' => 'Manajemen Dokumen', 'route' => 'document.index', 'perm' => 'document.view'],
            ['code' => 'csr.index', 'label' => 'Program CSR', 'route' => 'csr.index', 'perm' => 'csr.view'],
        ]],
        ['group' => 'Laporan', 'items' => [
            ['code' => 'report.mining', 'label' => 'Produksi Tambang', 'route' => 'report.mining', 'perm' => 'report.view'],
            ['code' => 'report.inventory', 'label' => 'Inventory', 'route' => 'report.inventory', 'perm' => 'report.view'],
            ['code' => 'report.sales', 'label' => 'Penjualan', 'route' => 'report.sales', 'perm' => 'report.view'],
            ['code' => 'report.hr', 'label' => 'HR', 'route' => 'report.hr', 'perm' => 'report.view'],
            ['code' => 'report.maintenance', 'label' => 'Pemeliharaan', 'route' => 'report.maintenance', 'perm' => 'report.view'],
        ]],
        ['group' => 'Persetujuan', 'items' => [
            ['code' => 'approval.pending', 'label' => 'Persetujuan Saya', 'route' => 'approval.index', 'perm' => 'approval.view'],
        ]],
    ];
@endphp
<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 -translate-x-full lg:translate-x-0 transition-transform duration-200 bg-slate-900 text-slate-300 flex flex-col">
    <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800 shrink-0">
        <div class="w-9 h-9 rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-900">M</div>
        <div>
            <div class="font-bold text-white text-sm tracking-wide">MINING ERP</div>
            <div class="text-[10px] text-slate-500">Integrated Operations</div>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-1 text-[13px]" x-data="{ open: null }">
        @foreach ($sidebarMenu as $item)
            @if (isset($item['group']))
                @php
                    $visibleItems = collect($item['items'])->filter(fn ($i) => auth()->user()->hasPermission($i['perm']));
                @endphp
                @if ($visibleItems->isNotEmpty())
                    <div x-data="{ open: @if(request()->routeIs(collect($item['items'])->pluck('code')->join(', '))) true @else false @endif }">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-slate-800 text-slate-400 uppercase text-[11px] tracking-wider font-semibold">
                            {{ $item['group'] }}
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div x-show="open" x-collapse x-cloak class="mt-1 space-y-0.5">
                            @foreach ($visibleItems as $sub)
                                @php $routeExists = \Illuminate\Support\Facades\Route::has($sub['route']); @endphp
                                <a href="{{ $routeExists ? route($sub['route']) : '#' }}"
                                   class="flex items-center px-3 py-1.5 rounded-md {{ request()->routeIs($sub['code']) ? 'bg-slate-800 text-white font-medium' : 'hover:bg-slate-800/60 hover:text-white' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-600 mr-2.5 {{ request()->routeIs($sub['code']) ? 'bg-amber-500' : '' }}"></span>
                                    {{ $sub['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                @if (auth()->user()->hasPermission($item['perm']))
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs($item['code']) ? 'bg-slate-800 text-white font-medium' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4 {{ request()->routeIs($item['code']) ? 'text-amber-500' : 'text-slate-500' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
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

<script>
    // x-collapse fallback
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Alpine) return;
        document.querySelectorAll('[x-collapse]').forEach(el => {
            el.style.display = el.getAttribute('x-show') !== null ? '' : '';
        });
    });
</script>
