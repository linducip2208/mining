@extends('layouts.app')
@section('title', ' - Laporan Sparepart')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Stok Sparepart</h1>
    <a href="{{ route('sparepart.reports', array_merge(request()->query(), ['export' => 'csv'])) }}" class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm min-h-[38px] inline-flex items-center">Export CSV</a>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <select name="type" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">@foreach(['balance' => 'Stock Balance', 'movement' => 'Movement', 'valuation' => 'Valuation', 'usage' => 'Usage', 'minstock' => 'Min/Reorder'] as $v => $l)<option value="{{ $v }}" @selected($type === $v)>{{ $l }}</option>@endforeach</select>
    <select name="warehouse_id" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Semua gudang</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected($warehouseId == $w->id)>{{ $w->name }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]" aria-label="Dari">
    <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]" aria-label="Sampai">
</form>

<x-table>
    <x-slot:head>
        @foreach($headers as $h)<th class="px-4 py-2.5">{{ $h }}</th>@endforeach
    </x-slot:head>
    <tbody>
        @forelse ($rows as $r)
        <tr class="hover:bg-slate-50">@foreach($r as $i => $c)<td class="px-4 py-2.5 {{ $i > 0 ? 'text-right' : 'font-mono' }} text-xs">{{ $c }}</td>@endforeach</tr>
        @empty
        <tr><td colspan="{{ count($headers) }}" class="px-4 py-10 text-center text-slate-400">Tidak ada data</td></tr>
        @endforelse
    </tbody>
</x-table>
@endsection
