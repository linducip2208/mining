@extends('layouts.app')
@section('title', ' - Work Order')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Work Order Pemeliharaan</h1>
    @can('work_order.create')<x-btn-create :href="route('work-orders.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Aset/Alat</th>
        <th class="px-4 py-2.5">Tipe</th><th class="px-4 py-2.5">Prioritas</th><th class="px-4 py-2.5 text-right">Biaya</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">
                @if ($item->equipment)
                    <a href="{{ route('equipment.show', $item->equipment) }}" class="text-amber-600 hover:underline">{{ $item->equipment?->name }}</a>
                @else
                    {{ $item->asset?->name }}
                @endif
            </td>
            <td class="px-4 py-2.5 text-xs">{{ $item->type }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->priority }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->actual_cost, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5"><a href="{{ route('work-orders.show', $item) }}" class="text-amber-600 text-xs hover:underline">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada WO</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection