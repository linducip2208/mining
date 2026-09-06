@extends('layouts.app')

@section('title', ' - Dashboard Operasi Tambang')

@section('content')
<x-ui.page-header title="Dashboard Operasi Tambang" description="Produksi, ritase, dan status aktivitas {{ $date }}.">
    <x-slot:actions>
        <x-ui.button variant="secondary" size="sm" icon="plus" :href="route('mining-activities.create')">Catat Aktivitas</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-filter-bar :route="route('mining.dashboard')" class="print:hidden">
    <x-filter-input name="date" label="Tanggal" type="date" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" placeholder="Semua Site" />
</x-filter-bar>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-ui.stat label="Produksi Hari Ini" :value="number_format($tons, 1) . ' T'" icon="pickaxe" />
    <x-ui.stat label="Trip / Ritase" :value="$trips" icon="flag" />
    <x-ui.stat label="Rata-rata Muatan" :value="number_format($avgPayload, 1) . ' T'" icon="truck" />
    <x-ui.stat label="Unit Aktif" :value="$units" icon="wrench" />
</div>

<div class="flex flex-wrap gap-2 mt-3">
    @foreach (['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'CANCELLED'] as $st)
    <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700">
        {{ $st }} <strong>{{ $byStatus[$st] ?? 0 }}</strong>
    </span>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-4">
    <x-ui.card title="Produksi per Shift">
        <div class="h-56"><canvas id="chartShift" role="img" aria-label="Grafik produksi per shift"></canvas></div>
        <x-ui.table>
            <x-slot:head><th class="px-4 py-2.5">Shift</th><th class="px-4 py-2.5 text-right">Trip</th><th class="px-4 py-2.5 text-right">Tonase</th></x-slot:head>
            @forelse ($byShift as $r)
            <tr class="hover:bg-slate-50 dark:hover:bg-white/5"><td class="px-4 py-2.5">{{ $r->name }}</td><td class="px-4 py-2.5 text-right">{{ $r->trips }}</td><td class="px-4 py-2.5 text-right font-semibold">{{ number_format($r->tons, 1) }}</td></tr>
            @empty<tr><td colspan="3"><x-ui.empty-state title="Belum ada data" /></td></tr>@endforelse
        </x-ui.table>
    </x-ui.card>
    <x-ui.card title="Produksi per Pit">
        <div class="h-56"><canvas id="chartPit" role="img" aria-label="Grafik produksi per pit"></canvas></div>
        <x-ui.table>
            <x-slot:head><th class="px-4 py-2.5">Pit</th><th class="px-4 py-2.5 text-right">Trip</th><th class="px-4 py-2.5 text-right">Tonase</th></x-slot:head>
            @forelse ($byPit as $r)
            <tr class="hover:bg-slate-50 dark:hover:bg-white/5"><td class="px-4 py-2.5">{{ $r->name }}</td><td class="px-4 py-2.5 text-right">{{ $r->trips }}</td><td class="px-4 py-2.5 text-right font-semibold">{{ number_format($r->tons, 1) }}</td></tr>
            @empty<tr><td colspan="3"><x-ui.empty-state title="Belum ada data" /></td></tr>@endforelse
        </x-ui.table>
    </x-ui.card>
</div>

<x-ui.card title="Aktivitas Terbaru" class="mt-4">
    <x-slot:actions><a href="{{ route('mining-activities.index') }}" class="text-xs text-amber-600 hover:underline">Semua →</a></x-slot:actions>
    <x-ui.table>
        <x-slot:head><th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Site / Pit</th><th class="px-4 py-2.5">Shift</th><th class="px-4 py-2.5">Unit</th><th class="px-4 py-2.5 text-right">Tonase</th><th class="px-4 py-2.5">Status</th></x-slot:head>
        @forelse ($latest as $a)
        <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
            <td class="px-4 py-2.5 font-mono text-xs">{{ $a->number }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $a->site?->name }} / {{ $a->pit?->name ?? '—' }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $a->shift?->name ?? '—' }}</td>
            <td class="px-4 py-2.5 font-mono text-xs">{{ $a->equipment?->code ?? '—' }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($a->tonnage, 1) }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$a->status" /></td>
        </tr>
        @empty<tr><td colspan="6"><x-ui.empty-state icon="pickaxe" title="Belum ada aktivitas" body="Catat aktivitas tambang pertama hari ini." /></td></tr>@endforelse
    </x-ui.table>
</x-ui.card>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dark = document.documentElement.classList.contains('dark');
    var grid = dark ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.12)';
    var tick = dark ? '#94a3b8' : '#64748b';
    var base = { responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { grid: { display: false }, ticks: { color: tick } }, y: { grid: { color: grid }, ticks: { color: tick } } } };
    new Chart(document.getElementById('chartShift'), { type: 'bar',
        data: { labels: @json($byShift->pluck('name')), datasets: [{ data: @json($byShift->pluck('tons')), backgroundColor: '#f59e0b', borderRadius: 6, maxBarThickness: 48 }] }, options: base });
    new Chart(document.getElementById('chartPit'), { type: 'bar',
        data: { labels: @json($byPit->pluck('name')), datasets: [{ data: @json($byPit->pluck('tons')), backgroundColor: '#6366f1', borderRadius: 6, maxBarThickness: 48 }] }, options: base });
});
</script>
@endpush
@endsection
