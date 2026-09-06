@extends('layouts.app')
@section('title', ' - Laporan BBM')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Konsumsi & Variansi BBM</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'fuel']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'fuel']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
    </div>
</div>
<x-filter-bar :route="route('report.fuel')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<div class="grid lg:grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Konsumsi per Unit</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Unit</th><th class="py-2 text-right">Liter</th><th class="py-2 text-right">Biaya</th><th class="py-2 text-right">Issue</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($perUnit as $unit => $r)
                <tr><td class="py-1.5 font-mono">{{ $unit }}</td><td class="py-1.5 text-right font-semibold">{{ number_format($r['liter'], 1) }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['cost'], 0) }}</td><td class="py-1.5 text-right">{{ $r['count'] }}</td></tr>
                @empty <tr><td colspan="4" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Anomali (WARNING / CRITICAL) — {{ $anomalies->count() }}</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Issue</th><th class="py-2">Unit</th><th class="py-2 text-right">L/H</th><th class="py-2">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($anomalies as $i)
                <tr><td class="py-1.5 font-mono text-xs">{{ $i->number }}</td><td class="py-1.5 font-mono">{{ $i->equipment?->code ?? $i->vehicle_plate }}</td><td class="py-1.5 text-right">{{ $i->liter_per_hour }}</td><td class="py-1.5"><x-status-badge :status="$i->variance_status === 'CRITICAL' ? 'BREAKDOWN' : 'PENDING'" /></td></tr>
                @empty <tr><td colspan="4" class="py-6 text-center text-emerald-500">Tidak ada anomali</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
