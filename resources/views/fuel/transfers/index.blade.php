@extends('layouts.app')

@section('title', ' - Transfer BBM')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Transfer BBM</h1>
        <p class="text-sm text-slate-500">Pemindahan antar tangki (depot → site)</p>
    </div>
    @can('fuel.create')
    <x-btn-create label="Buat Transfer" :href="route('fuel-transfers.create')" />
    @endcan
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Dari</th>
        <th class="px-4 py-2.5">Ke</th>
        <th class="px-4 py-2.5 text-right">Liter</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->transfer_date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->number }}</td>
        <td class="px-4 py-2.5">{{ $item->fromTank?->code }}</td>
        <td class="px-4 py-2.5">{{ $item->toTank?->code }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->liter, 1) }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right">
            @can('fuel.post')
            @if ($item->status === 'DRAFT')
            <form method="POST" action="{{ route('fuel-transfers.post', $item) }}" onsubmit="return confirm('Posting transfer ini?')">
                @csrf<button class="text-amber-600 hover:underline text-xs">Posting</button>
            </form>
            @endif
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
