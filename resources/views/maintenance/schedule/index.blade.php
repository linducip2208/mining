@extends('layouts.app')
@section('title', ' - Jadwal Pemeliharaan')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Jadwal Pemeliharaan</h1>
    @can('maintenance.create')<x-btn-create :href="route('maintenance-schedules.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Aset/Alat</th><th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5">Interval</th><th class="px-4 py-2.5">Terakhir</th><th class="px-4 py-2.5">Jatuh Tempo</th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50 {{ $item->next_due && $item->next_due->isPast() ? 'bg-red-50' : '' }}">
            <td class="px-4 py-2.5 font-medium">{{ $item->name }}</td>
            <td class="px-4 py-2.5">{{ $item->equipment?->name ?? $item->asset?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->type }}</td>
            <td class="px-4 py-2.5 text-xs">Setiap {{ $item->interval_value }} {{ strtolower($item->interval_type) }}</td>
            <td class="px-4 py-2.5">{{ $item->last_done?->format('d/m/Y') ?? '-' }}</td>
            <td class="px-4 py-2.5 {{ $item->next_due && $item->next_due->isPast() ? 'text-red-600 font-semibold' : '' }}">{{ $item->next_due?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada jadwal</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection