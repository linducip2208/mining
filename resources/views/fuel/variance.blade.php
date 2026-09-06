@extends('layouts.app')

@section('title', ' - Anomali BBM')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Anomali Konsumsi BBM</h1>
    <p class="text-sm text-slate-500">Issue WARNING / CRITICAL {{ $from }} s.d. {{ $to }} · <a href="{{ route('fuel.dashboard') }}" class="text-indigo-600 hover:underline">← Dashboard BBM</a></p>
</div>

<x-filter-bar :route="route('fuel.variance')">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5 text-right">Liter</th>
        <th class="px-4 py-2.5 text-right">L/H Aktual</th>
        <th class="px-4 py-2.5 text-right">L/H Standar</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($issues ?? [] as $i)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $i->issue_date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $i->number }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $i->equipment?->code ?? $i->vehicle_plate }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($i->liter, 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $i->liter_per_hour }}</td>
        <td class="px-4 py-2.5 text-right">{{ $i->equipment?->category?->standard_fuel_lph ?? '—' }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="($i->variance_status ?? '') === 'CRITICAL' ? 'BREAKDOWN' : 'PENDING'" /></td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-emerald-500">Tidak ada anomali — konsumsi normal</td></tr>
    @endforelse
</x-table>
@endsection
