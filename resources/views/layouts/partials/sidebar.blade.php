@php
    $groups = [
        ['label' => 'Operasi', 'icon' => 'pickaxe', 'items' => [
            ['label' => 'Mining Operations', 'route' => 'mining.dashboard', 'active' => 'mining.*', 'perm' => 'mining.view', 'icon' => 'pickaxe'], ['label' => 'Timbangan', 'route' => 'weighbridge-tickets.index', 'active' => 'weighbridge*', 'perm' => 'weighbridge.view', 'icon' => 'scale'], ['label' => 'Dispatch Board', 'route' => 'dispatch.dashboard', 'active' => 'dispatch.*', 'perm' => 'dispatch.view', 'icon' => 'flag']]],
        ['label' => 'Aset & Armada', 'icon' => 'truck', 'items' => [
            ['label' => 'Fleet Control', 'route' => 'fleet.dashboard', 'active' => 'fleet.*', 'perm' => 'fleet.view', 'icon' => 'truck'], ['label' => 'Peralatan', 'route' => 'equipment.index', 'active' => 'equipment.*', 'perm' => 'asset.view', 'icon' => 'wrench'], ['label' => 'Pemeliharaan', 'route' => 'work-orders.index', 'active' => 'work-orders.*', 'perm' => 'work_order.view', 'icon' => 'wrench']]],
        ['label' => 'Energi & Stok', 'icon' => 'fuel', 'items' => [
            ['label' => 'Fuel Control', 'route' => 'fuel.dashboard', 'active' => 'fuel.*', 'perm' => 'fuel.view', 'icon' => 'fuel'], ['label' => 'Stockpile Board', 'route' => 'stockpiles.dashboard', 'active' => 'stockpiles.*', 'perm' => 'stockpile.view', 'icon' => 'warehouse'], ['label' => 'Inventory', 'route' => 'stock.balance', 'active' => 'stock.*', 'perm' => 'stock.view', 'icon' => 'box']]],
        ['label' => 'Produksi & Penjualan', 'icon' => 'factory', 'items' => [
            ['label' => 'Produksi Crusher', 'route' => 'production-batches.index', 'active' => 'production*', 'perm' => 'production.view', 'icon' => 'factory'], ['label' => 'Sales Orders', 'route' => 'sales-orders.index', 'active' => 'sales-orders.*', 'perm' => 'sales_order.view', 'icon' => 'cart'], ['label' => 'Invoices', 'route' => 'invoices.index', 'active' => 'invoices.*', 'perm' => 'invoice.view', 'icon' => 'money']]],
        ['label' => 'Keuangan', 'icon' => 'bank', 'items' => [
            ['label' => 'Cash & Bank', 'route' => 'cash-accounts.index', 'active' => 'cash-accounts.*', 'perm' => 'finance.view', 'icon' => 'bank'], ['label' => 'Laba Rugi', 'route' => 'finance.pl', 'active' => 'finance.pl', 'perm' => 'ledger.view', 'icon' => 'chart'], ['label' => 'Kontrol Anggaran', 'route' => 'budgets.index', 'active' => 'budgets.*', 'perm' => 'budget.view', 'icon' => 'wallet']]],
        ['label' => 'Kontrol & Risiko', 'icon' => 'shield', 'items' => [
            ['label' => 'Approval Center', 'route' => 'approval.index', 'active' => 'approval.*', 'perm' => 'approval.view', 'icon' => 'check-circle'], ['label' => 'HSE & Compliance', 'route' => 'hse.dashboard', 'active' => 'hse.*', 'perm' => 'hse.view', 'icon' => 'heart'], ['label' => 'Audit Trail', 'route' => 'audit.index', 'active' => 'audit.*', 'perm' => 'audit.view', 'icon' => 'audit']]],
        ['label' => 'Insight', 'icon' => 'chart', 'items' => [['label' => 'Laporan', 'route' => 'report.mining', 'active' => 'report.*', 'perm' => 'report.view', 'icon' => 'chart'], ['label' => 'Forecast & Anomali', 'route' => 'forecast.index', 'active' => 'forecast.*', 'perm' => 'forecast.view', 'icon' => 'alert']]],
        ['label' => 'Administrasi', 'icon' => 'sliders', 'items' => [['label' => 'Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'perm' => 'user.view', 'icon' => 'users'], ['label' => 'Peran & Izin', 'route' => 'role.index', 'active' => 'role.*', 'perm' => 'role.view', 'icon' => 'shield'], ['label' => 'Pengaturan Sistem', 'route' => 'setting.index', 'active' => 'setting.*', 'perm' => 'setting.view', 'icon' => 'cog'], ['label' => 'Printer & Perangkat', 'route' => 'printer.index', 'active' => 'printer.*', 'perm' => 'printer.view', 'icon' => 'printer'], ['label' => 'Approval Workflow', 'route' => 'setting.approval-workflow', 'active' => 'setting.approval-workflow*', 'perm' => 'approval.workflow.view', 'icon' => 'layers'], ['label' => 'Kesehatan Sistem', 'route' => 'setting.health', 'active' => 'setting.health', 'perm' => 'setting.view', 'icon' => 'heart']]],
    ];
    $isActive = fn ($pattern) => collect((array) $pattern)->contains(fn ($p) => request()->routeIs($p));
@endphp
<div id="sbOverlay" class="fixed inset-0 z-20 bg-slate-950/60 hidden md:hidden"></div>
<aside id="sidebar" aria-label="Navigasi utama" class="fixed inset-y-0 left-0 z-30 flex flex-col text-slate-300 transition-[width,transform] duration-200 w-60 -translate-x-full md:translate-x-0" style="background:var(--brand-sidebar,#101923)">
    @php
        $sidebarLogo = \App\Services\BrandingService::assetUrl('branding.logo_sidebar');
    @endphp
    <div class="h-16 flex items-center gap-3 px-4 border-b border-white/10 shrink-0"><div class="w-9 h-9 flex-none rounded-xl bg-amber-400 text-[#101923] flex items-center justify-center font-black overflow-hidden">@if($sidebarLogo)<img src="{{ $sidebarLogo }}" alt="Logo {{ \App\Services\BrandingService::appName() }}" class="w-full h-full object-contain bg-white">@else{{ mb_substr(\App\Services\BrandingService::appName(), 0, 1) }}@endif</div><div class="sb-label min-w-0"><div class="font-bold text-white text-sm tracking-wide">{{ \App\Services\BrandingService::appName() }}</div><div class="text-[10px] text-slate-500">{{ \App\Services\BrandingService::companyName() }}</div></div><button type="button" id="sbCollapse" title="Ciutkan sidebar" aria-label="Ciutkan sidebar" class="sb-label ml-auto hidden md:flex p-1.5 rounded-md text-slate-500 hover:text-white hover:bg-white/10"><x-ui.icon name="menu" class="w-4 h-4" /></button></div>
    <div class="sb-label px-4 pt-4 pb-2 text-[10px] text-slate-500">WORKSPACE</div>
    <nav class="flex-1 overflow-y-auto nice-scroll px-2 pb-4 text-[13px]" aria-label="Menu modul">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 mb-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'hover:bg-white/5' }}"><x-ui.icon name="dashboard" class="w-[18px] h-[18px] {{ request()->routeIs('dashboard') ? 'text-amber-400' : 'text-slate-500' }}" /><span class="sb-label">Command Center</span></a>
        <div class="sb-label sidebar-section-label">Recently used</div>
        <a href="{{ route('approval.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:bg-white/5"><x-ui.icon name="check-circle" class="w-4 h-4 text-slate-500" /><span class="sb-label">Approval Center</span></a>
        <a href="{{ route('mining.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:bg-white/5"><x-ui.icon name="pickaxe" class="w-4 h-4 text-slate-500" /><span class="sb-label">Mining Operations</span></a>
        <div class="sb-label sidebar-section-label">Modules</div>
        @foreach ($groups as $group)
            @php $visible = collect($group['items'])->filter(function ($i) { $module = \App\Support\FeatureFlag::moduleForRoute($i['route']); return auth()->user()->hasPermission($i['perm']) && (! $module || \App\Support\FeatureFlag::enabled($module)); }); $active = $visible->contains(fn ($i) => $isActive($i['active'])); @endphp
            @if ($visible->isNotEmpty())
            <div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="mb-1">
                <button type="button" @click="open = !open" class="sb-groupbtn w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-white/5" :aria-expanded="open.toString()"><x-ui.icon name="{{ $group['icon'] }}" class="w-[17px] h-[17px] flex-none text-slate-500" /><span class="sb-label flex-1 text-left">{{ $group['label'] }}</span><x-ui.icon name="chevron-right" class="sb-label w-3.5 h-3.5 transition-transform" ::class="open && 'rotate-90'" /></button>
                <div x-show="open" x-cloak class="sb-sub mt-0.5 space-y-0.5">
                    @foreach ($visible as $item)
                        @php $itemActive = $isActive($item['active']); @endphp
                        <a href="{{ route($item['route']) }}" class="relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $itemActive ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}" @if($itemActive) aria-current="page" @endif><span class="absolute -left-[15px] h-5 w-[3px] rounded-full {{ $itemActive ? 'bg-amber-400' : 'bg-transparent' }}"></span><x-ui.icon name="{{ $item['icon'] }}" class="w-4 h-4 flex-none {{ $itemActive ? 'text-amber-400' : 'text-slate-500' }}" /><span class="sb-label truncate">{{ $item['label'] }}</span></a>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </nav>
    <div class="sb-label px-4 py-3 border-t border-white/10 text-[11px] text-slate-500">{{ \App\Services\BrandingService::appName() }} · v1.0</div>
</aside>
<script>
(function () { var sb = document.getElementById('sidebar'), ov = document.getElementById('sbOverlay'); function closeMobile(){ if(window.innerWidth < 768){sb.classList.add('-translate-x-full');ov.classList.add('hidden');} } window.toggleSidebar=function(){ if(window.innerWidth < 768){var hidden=sb.classList.toggle('-translate-x-full');ov.classList.toggle('hidden',hidden);} else {var c=document.documentElement.classList.toggle('sb-collapsed');try{localStorage.setItem('sb-collapsed',c?'1':'0')}catch(e){}} }; ov.addEventListener('click',closeMobile); document.getElementById('sbCollapse').addEventListener('click',window.toggleSidebar); }());
</script>
