@extends('layouts.app')
@section('title', ' - Laporan Budget')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Budget vs Aktual {{ $year }}</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
    </div>
</div>
<x-filter-bar :route="route('report.budget')" class="print:hidden">
    <x-filter-input name="year" label="Tahun" placeholder="2026" />
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'APPROVED' => 'Disetujui', 'REVISED' => 'Revisi', 'CLOSED' => 'Tutup', 'CANCELLED' => 'Batal']" />
</x-filter-bar>
<div class="grid md:grid-cols-4 gap-3 mb-4">
    <x-stat-card title="Total Pagu" :value="'Rp ' . number_format($totals['budget'], 0)" color="slate" />
    <x-stat-card title="Komitmen" :value="'Rp ' . number_format($totals['committed'], 0)" color="blue" />
    <x-stat-card title="Aktual" :value="'Rp ' . number_format($totals['actual'], 0)" color="amber" />
    <x-stat-card title="Sisa" :value="'Rp ' . number_format($totals['available'], 0)" color="green" />
</div>
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Budget</th><th class="py-2">Tipe</th><th class="py-2 text-right">Pagu</th><th class="py-2 text-right">Komitmen</th><th class="py-2 text-right">Aktual</th><th class="py-2 text-right">Sisa</th><th class="py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($budgets as $r)
            <tr><td class="py-1.5 font-mono text-xs">{{ $r['budget']->number }}</td><td class="py-1.5">{{ $r['budget']->type }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['report']['totals']['budget'], 0) }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['report']['totals']['committed'], 0) }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['report']['totals']['actual'], 0) }}</td><td class="py-1.5 text-right font-semibold {{ $r['report']['totals']['available'] < 0 ? 'text-red-600' : '' }}">Rp {{ number_format($r['report']['totals']['available'], 0) }}</td><td class="py-1.5"><x-status-badge :status="$r['budget']->status" /></td></tr>
            @empty <tr><td colspan="7" class="py-6 text-center text-slate-400">Tidak ada data</td></tr> @endforelse
        </tbody>
    </table>
</div>
@endsection
