@extends('layouts.app')
@section('title', ' - Laporan Kontrak')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Realisasi Kontrak</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
    </div>
</div>
<x-filter-bar :route="route('report.contract')" class="print:hidden">
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'ACTIVE' => 'Aktif', 'COMPLETED' => 'Selesai', 'EXPIRED' => 'Kedaluwarsa', 'CANCELLED' => 'Batal']" />
</x-filter-bar>
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Kontrak Customer</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Nomor</th><th class="py-2">Customer</th><th class="py-2 text-right">Volume</th><th class="py-2 text-right">Terkirim</th><th class="py-2 text-right">Sisa</th><th class="py-2 text-right">Progress</th><th class="py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($customers as $r)
            <tr><td class="py-1.5 font-mono text-xs">{{ $r['contract']->number }}</td><td class="py-1.5">{{ $r['contract']->customer?->name }}</td><td class="py-1.5 text-right">{{ number_format($r['real']['contract_qty'], 1) }}</td><td class="py-1.5 text-right">{{ number_format($r['real']['delivered_qty'], 1) }}</td><td class="py-1.5 text-right">{{ number_format($r['real']['remaining_qty'], 1) }}</td><td class="py-1.5 text-right font-semibold">{{ $r['real']['progress_pct'] }}%</td><td class="py-1.5"><x-status-badge :status="$r['contract']->status" /></td></tr>
            @empty <tr><td colspan="7" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
        </tbody>
    </table>
</div>
<div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Kontrak Supplier</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Nomor</th><th class="py-2">Supplier</th><th class="py-2 text-right">Diterima</th><th class="py-2 text-right">Tertagih</th><th class="py-2">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($suppliers as $r)
                <tr><td class="py-1.5 font-mono text-xs">{{ $r['contract']->number }}</td><td class="py-1.5">{{ $r['contract']->supplier?->name }}</td><td class="py-1.5 text-right">{{ number_format($r['real']['received_qty'], 1) }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['real']['billed_value'], 0) }}</td><td class="py-1.5"><x-status-badge :status="$r['contract']->status" /></td></tr>
                @empty <tr><td colspan="5" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Kontrak Hauling</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Nomor</th><th class="py-2 text-right">Trip</th><th class="py-2 text-right">Ton</th><th class="py-2 text-right">Nilai</th><th class="py-2 text-right">Shortfall</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($haulings as $r)
                <tr><td class="py-1.5 font-mono text-xs">{{ $r['contract']->number }}</td><td class="py-1.5 text-right">{{ $r['real']['trips'] }}</td><td class="py-1.5 text-right">{{ number_format($r['real']['tons'], 1) }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['real']['cost'], 0) }}</td><td class="py-1.5 text-right">{{ $r['real']['shortfall'] !== null ? number_format($r['real']['shortfall'], 1) : '—' }}</td></tr>
                @empty <tr><td colspan="5" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
