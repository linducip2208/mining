@extends('layouts.app')

@section('title', ' - Dashboard')

@section('content')
<x-filter-bar :route="route('dashboard')">
    <x-filter-input name="site_id" label="Site" type="select" :options="\App\Models\Site::pluck('name', 'id')->all()" placeholder="Semua Site" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <x-stat-card title="Produksi Hari Ini" :value="number_format($produksiHariIni, 2) . ' Ton'" color="amber" />
    <x-stat-card title="Produksi Bulan Ini" :value="number_format($produksiBulanIni, 2) . ' Ton'" color="amber" :sub="'Output crusher: ' . number_format($outputCrusher, 2) . ' Ton'" />
    <x-stat-card title="Penjualan Bulan Ini" :value="'Rp ' . number_format($salesBulanIni, 0, ',', '.')" color="green" :sub="'Hari ini: Rp ' . number_format($salesHariIni, 0, ',', '.')" />
    <x-stat-card title="Piutang (AR)" :value="'Rp ' . number_format($piutang, 0, ',', '.')" color="red" :sub="$outstandingInv . ' faktur outstanding'" />
    <x-stat-card title="Kas" :value="'Rp ' . number_format($kas, 0, ',', '.')" color="indigo" />
    <x-stat-card title="Revenue vs Expense" :value="'Rp ' . number_format($revenue, 0, ',', '.')" color="blue" :sub="'Beban: Rp ' . number_format($expense, 0, ',', '.')" />
    <x-stat-card title="Deposit Customer" :value="'Rp ' . number_format($depositTotal, 0, ',', '.')" color="green" />
    <x-stat-card title="Karyawan Aktif" :value="number_format($employeeActive)" color="slate" :sub="'Hadir hari ini: ' . number_format($hadir)" />
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-700 mb-4">Produksi (14 Hari)</h3>
        <canvas id="chartProd" height="110"></canvas>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-700 mb-4">Penjualan (14 Hari)</h3>
        <canvas id="chartSales" height="110"></canvas>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-700 mb-4">Tonase per Site (Bulan Ini)</h3>
        <canvas id="chartSites" height="110"></canvas>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-700 mb-4">Stok Terbesar</h3>
        <canvas id="chartStock" height="110"></canvas>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4 mt-6">
    @auth
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-slate-700">Persetujuan Tertunda</h3>
            <a href="{{ route('approval.index') }}" class="text-xs text-amber-600 hover:underline">Lihat semua</a>
        </div>
        @forelse ($pendingApprovals as $apr)
            <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                <div>
                    <div class="font-medium text-slate-700">{{ $apr->module }} · {{ $apr->transaction_number }}</div>
                    <div class="text-xs text-slate-400">Rp {{ number_format($apr->amount, 0, ',', '.') }}</div>
                </div>
                <x-status-badge :status="$apr->status" />
            </div>
        @empty
            <p class="text-sm text-slate-400 py-4 text-center">Tidak ada persetujuan tertunda</p>
        @endforelse
    </div>
    @endauth

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-700 mb-3">Stok Kritis</h3>
        @forelse ($kritis as $item)
            <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                <div class="text-slate-700">{{ $item->code }} · {{ $item->name }}</div>
                <div class="text-xs text-red-600 font-semibold">Min: {{ number_format($item->min_stock, 2) }}</div>
            </div>
        @empty
            <p class="text-sm text-slate-400 py-4 text-center">Tidak ada stok kritis</p>
        @endforelse
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-slate-700">Status Operasional</h3>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="p-3 rounded-lg bg-slate-50">
                <div class="text-xs text-slate-400">Work Order Terbuka</div>
                <div class="text-xl font-bold text-slate-700">{{ $woOpen }}</div>
            </div>
            <div class="p-3 rounded-lg bg-slate-50">
                <div class="text-xs text-slate-400">Downtime (Jam/Bulan)</div>
                <div class="text-xl font-bold text-slate-700">{{ number_format($downtime, 1) }}</div>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 col-span-2">
                <div class="text-xs text-slate-400 mb-1">Top Customer Bulan Ini</div>
                @forelse ($topCustomers as $tc)
                    <div class="flex justify-between py-0.5">
                        <span class="text-slate-600">{{ $tc->name }}</span>
                        <span class="font-medium">Rp {{ number_format($tc->total, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <span class="text-slate-400">Belum ada penjualan</span>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fmt = v => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v);
    const common = { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: fmt } } } };

    @php
        $prodLabels = $prodTrend->pluck('d')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'));
        $salesLabels = $salesTrend->pluck('d')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'));
    @endphp

    new Chart(document.getElementById('chartProd'), {
        type: 'line',
        data: { labels: @json($prodLabels), datasets: [{ data: @json($prodTrend->pluck('t')), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.1)', fill: true, tension: .35 }] },
        options: common
    });
    new Chart(document.getElementById('chartSales'), {
        type: 'line',
        data: { labels: @json($salesLabels), datasets: [{ data: @json($salesTrend->pluck('t')), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', fill: true, tension: .35 }] },
        options: common
    });
    new Chart(document.getElementById('chartSites'), {
        type: 'bar',
        data: { labels: @json($tonnagePerSite->pluck('name')), datasets: [{ data: @json($tonnagePerSite->pluck('total')), backgroundColor: '#f59e0b', borderRadius: 6 }] },
        options: common
    });
    new Chart(document.getElementById('chartStock'), {
        type: 'bar',
        data: { labels: @json($stockBalances->pluck('name')), datasets: [{ data: @json($stockBalances->pluck('bal')), backgroundColor: '#6366f1', borderRadius: 6 }] },
        options: common
    });
});
</script>
@endpush
@endsection
