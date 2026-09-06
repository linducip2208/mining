@extends('layouts.app')
@section('title', ' - Laporan Quality')
@section('content')
@php use App\Support\HumanLabel; @endphp
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Hasil Quality</h1>
    <div class="flex gap-2">
        <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
        <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
    </div>
</div>
<x-filter-bar :route="route('report.quality')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<div class="grid md:grid-cols-3 gap-3 mb-4">
    <x-stat-card title="Total Sampel" :value="$total" color="slate" />
    <x-stat-card title="Pass Rate" :value="$passRate . '%'" color="green" />
    <x-stat-card title="Hold Aktif" :value="$holds" color="red" />
</div>
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold text-sm mb-3">Fail Rate per Parameter</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Parameter</th><th class="py-2 text-right">Uji</th><th class="py-2 text-right">Gagal</th><th class="py-2 text-right">Fail Rate</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($perParam as $code => $r)
            <tr><td class="py-1.5">{{ HumanLabel::label($code) }}</td><td class="py-1.5 text-right">{{ $r['tests'] }}</td><td class="py-1.5 text-right">{{ $r['fails'] }}</td><td class="py-1.5 text-right font-semibold {{ $r['fail_rate'] > 10 ? 'text-red-600' : '' }}">{{ $r['fail_rate'] }}%</td></tr>
            @empty <tr><td colspan="4" class="py-6 text-center text-slate-400">Belum ada hasil uji periode ini</td></tr> @endforelse
        </tbody>
    </table>
</div>
@endsection
