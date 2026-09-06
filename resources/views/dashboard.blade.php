@extends('layouts.app')

@section('title', ' - Executive Command Center')

@section('content')
<x-ui.page-header title="Executive Command Center" description="Kondisi operasi, keuangan, dan risiko hari ini — klik kartu untuk drill-down.">
    <x-slot:actions>
        <x-ui.button variant="secondary" size="sm" icon="chart" :href="route('executive.index')">Mode Eksekutif</x-ui.button>
        <x-ui.button variant="secondary" size="sm" icon="printer" type="button" onclick="window.print()">Cetak</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-filter-bar :route="route('dashboard')" class="print:hidden">
    <x-filter-input name="site_id" label="Site" type="select" :options="\App\Models\Site::pluck('name', 'id')->all()" placeholder="Semua Site" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

{{-- ROW 1: operasi + komersial --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
    <x-ui.stat label="Produksi Hari Ini" :value="number_format($produksiHariIni, 1) . ' T'" icon="pickaxe" :href="route('mining-activities.index')" />
    <x-ui.stat label="Produksi Bulan Ini" :value="number_format($produksiBulanIni, 0) . ' T'" :sub="'Crusher: ' . number_format($outputCrusher, 0) . ' T'" icon="factory" :href="route('production-batches.index')" />
    <x-ui.stat label="Penjualan Bulan Ini" :value="'Rp ' . number_format($salesBulanIni, 0, ',', '.')" :sub="'Hari ini Rp ' . number_format($salesHariIni, 0, ',', '.')" icon="money" :href="route('invoices.index')" />
    <x-ui.stat label="Margin (Rev − Beban)" :value="'Rp ' . number_format($revenue - $expense, 0, ',', '.')" :sub="'Rev Rp ' . number_format($revenue, 0, ',', '.')" icon="chart" :href="route('finance.pl')" />
    <x-ui.stat label="Kas" :value="'Rp ' . number_format($kas, 0, ',', '.')" icon="bank" :href="route('finance.cash_flow')" />
    <x-ui.stat label="Piutang (AR)" :value="'Rp ' . number_format($piutang, 0, ',', '.')" :sub="$outstandingInv . ' faktur outstanding'" icon="clock" :href="route('finance.ar_aging')" />
</div>

{{-- ROW 2: risiko + SDM --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
    <x-ui.stat label="Stok Kritis" :value="count($kritis)" icon="alert" :href="route('stock.balance')" />
    <x-ui.stat label="WO Terbuka" :value="$woOpen" :sub="number_format($downtime, 1) . ' jam downtime/bln'" icon="wrench" :href="route('work-orders.index')" />
    <x-ui.stat label="Karyawan Aktif" :value="number_format($employeeActive)" :sub="'Hadir: ' . number_format($hadir)" icon="users" :href="route('employees.index')" />
    <x-ui.stat label="Deposit Customer" :value="'Rp ' . number_format($depositTotal, 0, ',', '.')" icon="wallet" :href="route('deposit.index')" />
</div>

{{-- ROW 3: tren --}}
<div class="grid lg:grid-cols-2 gap-4 mt-4">
    <x-ui.card title="Produksi 14 Hari (ton)">
        <x-slot:actions><a href="{{ route('report.mining') }}" class="text-xs text-amber-600 hover:underline">Laporan →</a></x-slot:actions>
        <div class="h-56"><canvas id="chartProd" aria-label="Grafik tren produksi" role="img"></canvas></div>
    </x-ui.card>
    <x-ui.card title="Penjualan 14 Hari (Rp)">
        <x-slot:actions><a href="{{ route('report.sales') }}" class="text-xs text-amber-600 hover:underline">Laporan →</a></x-slot:actions>
        <div class="h-56"><canvas id="chartSales" aria-label="Grafik tren penjualan" role="img"></canvas></div>
    </x-ui.card>
    <x-ui.card title="Tonase per Site (Bulan Ini)">
        <div class="h-56"><canvas id="chartSites" aria-label="Grafik tonase per site" role="img"></canvas></div>
    </x-ui.card>
    <x-ui.card title="Beban 6 Periode Terakhir">
        <x-slot:actions><a href="{{ route('finance.pl') }}" class="text-xs text-amber-600 hover:underline">Laba rugi →</a></x-slot:actions>
        <div class="h-56"><canvas id="chartExp" aria-label="Grafik beban per periode" role="img"></canvas></div>
    </x-ui.card>
</div>

{{-- ROW 4: aksi + alert --}}
<div class="grid lg:grid-cols-3 gap-4 mt-4">
    <x-ui.card title="Persetujuan Tertunda">
        <x-slot:actions><a href="{{ route('approval.index') }}" class="text-xs text-amber-600 hover:underline">Lihat semua</a></x-slot:actions>
        @forelse ($pendingApprovals as $apr)
            <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700/50 text-sm last:border-0">
                <div class="min-w-0">
                    <div class="font-medium truncate">{{ $apr->module }} · {{ $apr->transaction_number }}</div>
                    <div class="text-xs text-slate-400">Rp {{ number_format($apr->amount, 0, ',', '.') }} · {{ $apr->submitted_at?->diffForHumans() }}</div>
                </div>
                <x-status-badge :status="$apr->status" />
            </div>
        @empty
            <x-ui.empty-state icon="check-circle" title="Nihil" body="Tidak ada persetujuan tertunda." />
        @endforelse
    </x-ui.card>

    <x-ui.card title="Stok Kritis">
        <x-slot:actions><a href="{{ route('stock.balance') }}" class="text-xs text-amber-600 hover:underline">Saldo →</a></x-slot:actions>
        @forelse ($kritis as $item)
            <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700/50 text-sm last:border-0">
                <div class="truncate">{{ $item->code }} · {{ $item->name }}</div>
                <div class="text-xs text-red-600 dark:text-red-400 font-semibold flex-none ml-2">Min: {{ number_format($item->min_stock, 1) }}</div>
            </div>
        @empty
            <x-ui.empty-state icon="box" title="Aman" body="Tidak ada stok di bawah minimum." />
        @endforelse
    </x-ui.card>

    <x-ui.card title="Modul Operasi">
        <div class="grid grid-cols-2 gap-2 text-sm">
            @foreach ([['Fleet', 'fleet.dashboard', 'truck'], ['BBM', 'fuel.dashboard', 'fuel'], ['Dispatch', 'dispatch.dashboard', 'flag'], ['Stockpile', 'stockpiles.dashboard', 'warehouse'], ['HSE', 'hse.dashboard', 'heart'], ['Budget', 'budgets.index', 'wallet']] as [$label, $r, $ic])
            <a href="{{ route($r) }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-amber-400 hover:shadow-sm transition">
                <x-ui.icon :name="$ic" class="w-4 h-4 text-amber-500" />{{ $label }}
            </a>
            @endforeach
        </div>
        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700/50 text-sm">
            <div class="text-xs text-slate-400 mb-1.5">Top Customer Bulan Ini</div>
            @forelse ($topCustomers as $tc)
                <div class="flex justify-between py-0.5 gap-2">
                    <span class="text-slate-600 dark:text-slate-300 truncate">{{ $tc->name }}</span>
                    <span class="font-medium flex-none">Rp {{ number_format($tc->total, 0, ',', '.') }}</span>
                </div>
            @empty
                <span class="text-slate-400">Belum ada penjualan</span>
            @endforelse
        </div>
    </x-ui.card>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dark = document.documentElement.classList.contains('dark');
    var grid = dark ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.12)';
    var tick = dark ? '#94a3b8' : '#64748b';
    var fmt = function (v) { return new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v); };
    var base = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + new Intl.NumberFormat('id-ID').format(c.parsed.y); } } } },
        scales: { x: { grid: { display: false }, ticks: { color: tick, maxTicksLimit: 8 } }, y: { grid: { color: grid }, ticks: { color: tick, callback: fmt } } }
    };
    @php
        $prodLabels = $prodTrend->pluck('d')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'));
        $salesLabels = $salesTrend->pluck('d')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'));
    @endphp
    new Chart(document.getElementById('chartProd'), { type: 'line',
        data: { labels: @json($prodLabels), datasets: [{ data: @json($prodTrend->pluck('t')), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.12)', fill: true, tension: .35, pointRadius: 2 }] }, options: base });
    new Chart(document.getElementById('chartSales'), { type: 'line',
        data: { labels: @json($salesLabels), datasets: [{ data: @json($salesTrend->pluck('t')), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .35, pointRadius: 2 }] }, options: base });
    new Chart(document.getElementById('chartSites'), { type: 'bar',
        data: { labels: @json($tonnagePerSite->pluck('name')), datasets: [{ data: @json($tonnagePerSite->pluck('total')), backgroundColor: '#f59e0b', borderRadius: 6, maxBarThickness: 42 }] }, options: base });
    new Chart(document.getElementById('chartExp'), { type: 'bar',
        data: { labels: @json($revExpTrend->pluck('period')), datasets: [{ data: @json($revExpTrend->pluck('expense')), backgroundColor: '#6366f1', borderRadius: 6, maxBarThickness: 42 }] }, options: base });
});
</script>
@endpush
@endsection
