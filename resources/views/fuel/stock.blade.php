@extends('layouts.app')

@section('title', ' - Stok BBM')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Stok BBM per Tangki</h1>
    <p class="text-sm text-slate-500"><a href="{{ route('fuel.dashboard') }}" class="text-indigo-600 hover:underline">← Dashboard BBM</a></p>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tangki</th>
        <th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5 text-right">Saldo (L)</th>
        <th class="px-4 py-2.5 text-right">HPP/L</th>
        <th class="px-4 py-2.5 text-right">Nilai Persediaan</th>
    </x-slot:head>
    @forelse ($tanks ?? [] as $t)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><span class="font-mono font-semibold">{{ $t->code }}</span> <span class="text-slate-500">{{ $t->name }}</span></td>
        <td class="px-4 py-2.5">{{ $t->site?->name }}</td>
        <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($t->balance, 2) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($t->avg_cost, 2) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($t->value, 0) }}</td>
    </tr>
    @empty
    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada tangki aktif</td></tr>
    @endforelse
</x-table>
@endsection
