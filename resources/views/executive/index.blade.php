@extends('layouts.app')

@section('title', ' - Executive Dashboard')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Executive Command Center</h1>
    <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }} (mode: {{ $mode }})</p>
</div>

<x-filter-bar :route="route('executive.index')">
    <x-filter-input name="mode" label="Mode" type="select" :options="['today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'mtd' => 'Bulan Berjalan', 'ytd' => 'Tahun Berjalan', 'custom' => 'Kustom']" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
    <x-filter-input name="company_id" label="Perusahaan" type="select" :options="$companies ?? []" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
</x-filter-bar>

<div class="grid md:grid-cols-4 gap-3 mb-4">
    <x-stat-card title="Produksi Tambang" :value="number_format($mining->tons ?? 0, 1) . ' T'" :sub="number_format($mining->hours ?? 0, 1) . ' jam kerja'" color="slate" />
    <x-stat-card title="Output Crusher" :value="number_format($production->net ?? 0, 1) . ' T'" :sub="'Loss ' . number_format($production->loss ?? 0, 1) . ' T'" color="blue" />
    <x-stat-card title="Pendapatan" :value="'Rp ' . number_format($revenue ?? 0, 0)" color="green" />
    <x-stat-card title="Biaya Tambang" :value="'Rp ' . number_format($cost['total_cost'] ?? 0, 0)" :sub="'Rp ' . number_format($cost['cost_per_ton'] ?? 0, 0) . '/ton'" color="amber" />
    <x-stat-card title="BBM" :value="number_format($fuel->liter ?? 0, 0) . ' L'" :sub="'Rp ' . number_format($fuel->cost ?? 0, 0)" color="amber" />
    <x-stat-card title="Nilai Stok" :value="'Rp ' . number_format($stockValue ?? 0, 0)" color="indigo" />
    <x-stat-card title="Piutang / Utang" :value="'Rp ' . number_format($ar ?? 0, 0)" :sub="'Utang Rp ' . number_format($ap ?? 0, 0)" color="slate" />
    <x-stat-card title="Approval Pending" :value="$pendingApprovals ?? 0" :sub="$criticalStock . ' stok kritis · ' . $expiredPermits . ' izin kedaluwarsa'" color="red" />
</div>

<div class="grid md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-2">Armada</div>
        <div class="text-xs text-slate-500 space-y-1">
            <div class="flex justify-between"><span>Unit</span><span class="font-semibold">{{ $fleet['totals']['units'] ?? 0 }}</span></div>
            <div class="flex justify-between"><span>Jam operasi</span><span class="font-semibold">{{ number_format($fleet['totals']['operating'] ?? 0, 1) }}</span></div>
            <div class="flex justify-between"><span>Downtime</span><span class="font-semibold">{{ number_format($fleet['totals']['downtime'] ?? 0, 1) }}</span></div>
            <div class="flex justify-between"><span>PA %</span><span class="font-semibold">{{ $fleet['totals']['pa_pct'] ?? 0 }}%</span></div>
        </div>
        <a href="{{ route('fleet.dashboard') }}" class="text-xs text-indigo-600 hover:underline">Detail armada →</a>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-2">HSE</div>
        <div class="text-xs text-slate-500 space-y-1">
            <div class="flex justify-between"><span>Insiden</span><span class="font-semibold">{{ $hse['incidents'] ?? 0 }}</span></div>
            <div class="flex justify-between"><span>Near miss</span><span class="font-semibold">{{ $hse['near_miss'] ?? 0 }}</span></div>
            <div class="flex justify-between"><span>Action terbuka</span><span class="font-semibold">{{ $hse['open_actions'] ?? 0 }}</span></div>
            <div class="flex justify-between"><span>Hari tanpa kecelakaan</span><span class="font-semibold">{{ $hse['safe_days'] ?? '—' }}</span></div>
        </div>
        <a href="{{ route('hse.dashboard') }}" class="text-xs text-indigo-600 hover:underline">Detail HSE →</a>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-2">Aksi Cepat</div>
        <div class="flex flex-col gap-1.5 text-xs">
            <a href="{{ route('approval.index') }}" class="text-indigo-600 hover:underline">Persetujuan tertunda ({{ $pendingApprovals ?? 0 }}) →</a>
            <a href="{{ route('forecast.index') }}" class="text-indigo-600 hover:underline">Forecast & anomali →</a>
            <a href="{{ route('finance.pl') }}" class="text-indigo-600 hover:underline">Laba rugi →</a>
            <a href="{{ route('cost.dashboard') }}" class="text-indigo-600 hover:underline">Biaya per ton →</a>
        </div>
    </div>
</div>
@endsection
