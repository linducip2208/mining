@extends('layouts.app')

@section('title', ' - ' . ($title ?? 'Downtime Armada'))

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">{{ $title ?? 'Downtime Armada' }}</h1>
        <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }} · <a href="{{ route('fleet.dashboard') }}" class="text-indigo-600 hover:underline">Kembali ke dashboard</a></p>
    </div>
</div>

<x-filter-bar :route="url()->current()">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Downtime (H)</th>
        <th class="px-4 py-2.5 text-right">Breakdown (H)</th>
        <th class="px-4 py-2.5 text-right">Kalender (H)</th>
        <th class="px-4 py-2.5 text-right">Share Downtime %</th>
        <th class="px-4 py-2.5 text-right">MA %</th>
    </x-slot:head>
    @forelse ($rows ?? [] as $r)
    @php $cal = $r['kpi']['calendar_hours'] ?: 1; @endphp
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><span class="font-mono font-semibold">{{ $r['unit']->code }}</span> <span class="text-slate-500">{{ $r['unit']->name }}</span></td>
        <td class="px-4 py-2.5"><x-status-badge :status="$r['unit']->status" /></td>
        <td class="px-4 py-2.5 text-right font-semibold text-red-600">{{ number_format($r['kpi']['downtime_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['breakdown_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['calendar_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ round($r['kpi']['downtime_hours'] / $cal * 100, 1) }}%</td>
        <td class="px-4 py-2.5 text-right">{{ $r['kpi']['ma_pct'] }}%</td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada unit pada filter ini</td></tr>
    @endforelse
</x-table>
@endsection
