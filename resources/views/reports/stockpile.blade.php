@extends('layouts.app')
@section('title', ' - Laporan Stockpile')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Saldo & Rekonsiliasi Stockpile</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'stockpile']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'stockpile']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
    </div>
</div>
<x-filter-bar :route="route('report.stockpile')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Saldo per Pile</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Pile</th><th class="py-2">Site</th><th class="py-2">Material</th><th class="py-2 text-right">Saldo (T)</th><th class="py-2 text-right">Kapasitas (T)</th><th class="py-2 text-right">Isi %</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($piles as $p)
            <tr><td class="py-1.5 font-mono">{{ $p->code }}</td><td class="py-1.5">{{ $p->site?->name }}</td><td class="py-1.5">{{ $p->item?->name }}</td><td class="py-1.5 text-right font-semibold">{{ number_format($p->balance, 2) }}</td><td class="py-1.5 text-right">{{ number_format($p->capacity_ton, 1) }}</td><td class="py-1.5 text-right">{{ $p->capacity_ton > 0 ? round($p->balance / $p->capacity_ton * 100, 1) : 0 }}%</td></tr>
            @empty <tr><td colspan="6" class="py-6 text-center text-slate-400">Tidak ada data</td></tr> @endforelse
        </tbody>
    </table>
</div>
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold text-sm mb-3">Riwayat Survei & Variansi</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Tanggal</th><th class="py-2">Pile</th><th class="py-2 text-right">Sistem</th><th class="py-2 text-right">Survei</th><th class="py-2 text-right">Variansi</th><th class="py-2 text-right">Var %</th><th class="py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($surveys as $s)
            <tr><td class="py-1.5">{{ $s->survey_date }}</td><td class="py-1.5 font-mono">{{ $s->stockpile?->code }}</td><td class="py-1.5 text-right">{{ number_format($s->system_balance, 2) }}</td><td class="py-1.5 text-right">{{ number_format($s->survey_balance, 2) }}</td><td class="py-1.5 text-right">{{ number_format($s->variance, 2) }}</td><td class="py-1.5 text-right">{{ $s->variance_pct }}%</td><td class="py-1.5"><x-status-badge :status="$s->status" /></td></tr>
            @empty <tr><td colspan="7" class="py-6 text-center text-slate-400">Belum ada survei periode ini</td></tr> @endforelse
        </tbody>
    </table>
</div>
@endsection
