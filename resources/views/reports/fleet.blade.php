@extends('layouts.app')
@section('title', ' - Laporan Armada')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Armada & Utilisasi</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
    </div>
</div>
<x-filter-bar :route="route('report.fleet')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites_list ?? []" />
</x-filter-bar>
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Unit</th><th class="py-2">Status</th><th class="py-2 text-right">Operasi (H)</th><th class="py-2 text-right">Idle (H)</th><th class="py-2 text-right">Downtime (H)</th><th class="py-2 text-right">PA %</th><th class="py-2 text-right">Util %</th><th class="py-2 text-right">Biaya</th><th class="py-2 text-right">Rp/Jam</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $r)
            <tr><td class="py-1.5 font-mono">{{ $r['unit']->code }}</td><td class="py-1.5"><x-status-badge :status="$r['unit']->status" /></td><td class="py-1.5 text-right">{{ number_format($r['kpi']['operating_hours'], 1) }}</td><td class="py-1.5 text-right">{{ number_format($r['kpi']['idle_hours'], 1) }}</td><td class="py-1.5 text-right">{{ number_format($r['kpi']['downtime_hours'], 1) }}</td><td class="py-1.5 text-right font-semibold">{{ $r['kpi']['pa_pct'] }}%</td><td class="py-1.5 text-right font-semibold">{{ $r['kpi']['utilization_pct'] }}%</td><td class="py-1.5 text-right">Rp {{ number_format($r['kpi']['total_cost'], 0) }}</td><td class="py-1.5 text-right">Rp {{ number_format($r['kpi']['cost_per_hour'], 0) }}</td></tr>
            @empty <tr><td colspan="9" class="py-6 text-center text-slate-400">Tidak ada data</td></tr> @endforelse
        </tbody>
    </table>
</div>
@endsection
