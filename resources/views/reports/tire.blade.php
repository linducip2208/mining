@extends('layouts.app')
@section('title', ' - Laporan Ban')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Lifetime Ban</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'tire']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'tire']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
    </div>
</div>
<x-filter-bar :route="route('report.tire')" class="print:hidden">
    <x-filter-input name="status" label="Status" type="select" :options="['NEW' => 'Baru', 'STOCK' => 'Stok', 'INSTALLED' => 'Terpasang', 'REPAIR' => 'Repair', 'SCRAP' => 'Scrap']" />
</x-filter-bar>
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Seri</th><th class="py-2">Merek/Ukuran</th><th class="py-2">Status</th><th class="py-2">Unit</th><th class="py-2 text-right">Biaya Beli</th><th class="py-2 text-right">Repair</th><th class="py-2 text-right">Total</th><th class="py-2 text-right">Lifetime HM</th><th class="py-2 text-right">Rp/HM</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $r)
            <tr><td class="py-1.5 font-mono">{{ $r['tire']->serial_no }}</td><td class="py-1.5">{{ trim($r['tire']->brand . ' ' . $r['tire']->size) }}</td><td class="py-1.5"><x-status-badge :status="$r['tire']->status" /></td><td class="py-1.5 font-mono">{{ $r['tire']->equipment?->code ?? '—' }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['tire']->purchase_cost, 0) }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['repairs'], 0) }}</td><td class="py-1.5 text-right font-semibold">Rp {{ number_format($r['total_cost'], 0) }}</td><td class="py-1.5 text-right">{{ number_format($r['life_hm'], 1) }}</td><td class="py-1.5 text-right">{{ $r['cost_per_hm'] !== null ? 'Rp ' . number_format($r['cost_per_hm'], 0) : '—' }}</td></tr>
            @empty <tr><td colspan="9" class="py-6 text-center text-slate-400">Tidak ada data</td></tr> @endforelse
        </tbody>
    </table>
</div>
@endsection
