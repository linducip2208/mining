@extends('layouts.app')
@section('title', ' - Laporan Dispatch')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Produktivitas Dispatch & Cycle Time</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'dispatch']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'dispatch']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
    </div>
</div>
<x-filter-bar :route="route('report.dispatch')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Produktivitas per Truk ({{ $done->count() }} trip selesai)</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Truk</th><th class="py-2 text-right">Trip</th><th class="py-2 text-right">Ton</th><th class="py-2 text-right">Ton/Trip</th><th class="py-2 text-right">Cycle Avg (mnt)</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($perTruck as $truck => $r)
                <tr><td class="py-1.5 font-mono">{{ $truck }}</td><td class="py-1.5 text-right">{{ $r['trips'] }}</td><td class="py-1.5 text-right font-semibold">{{ number_format($r['tons'], 1) }}</td><td class="py-1.5 text-right">{{ $r['ton_per_trip'] }}</td><td class="py-1.5 text-right">{{ $r['cycle_avg'] ?: '—' }}</td></tr>
                @empty <tr><td colspan="5" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Produktivitas per Rute</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Rute</th><th class="py-2 text-right">Trip</th><th class="py-2 text-right">Ton</th><th class="py-2 text-right">Ton-Km</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($perRoute as $route => $r)
                <tr><td class="py-1.5">{{ $route }}</td><td class="py-1.5 text-right">{{ $r['trips'] }}</td><td class="py-1.5 text-right font-semibold">{{ number_format($r['tons'], 1) }}</td><td class="py-1.5 text-right">{{ number_format($r['ton_km'], 1) }}</td></tr>
                @empty <tr><td colspan="4" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
