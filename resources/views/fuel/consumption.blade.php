@extends('layouts.app')
@php use App\Support\StatusLabel; @endphp

@section('title', ' - Konsumsi BBM')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Konsumsi BBM</h1>
    <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }} · <a href="{{ route('fuel.dashboard') }}" class="text-indigo-600 hover:underline">← Dashboard BBM</a></p>
</div>

<x-filter-bar :route="route('fuel.consumption')">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Tangki</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5 text-right">Liter</th>
        <th class="px-4 py-2.5 text-right">Biaya</th>
        <th class="px-4 py-2.5 text-right">L/H</th>
        <th class="px-4 py-2.5">Variansi</th>
    </x-slot:head>
    @forelse ($issues ?? [] as $i)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $i->issue_date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $i->number }}</td>
        <td class="px-4 py-2.5">{{ $i->tank?->code }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $i->equipment?->code ?? $i->vehicle_plate }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($i->liter, 1) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($i->total_cost, 0) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $i->liter_per_hour }}</td>
        <td class="px-4 py-2.5">{{ $i->variance_status ? StatusLabel::label($i->variance_status) : '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada konsumsi periode ini</td></tr>
    @endforelse
</x-table>
@endsection
