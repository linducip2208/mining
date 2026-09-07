@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp
@section('title', ' - Jadwal Pemeliharaan')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Jadwal Pemeliharaan</h1>
    <div class="flex flex-wrap gap-2">
        @can('work_order.create')<form action="{{ route('maintenance-schedules.generate') }}" method="POST" class="inline">@csrf<button class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-medium min-h-[38px]">Generate WO Jatuh Tempo</button></form>@endcan
        @can('maintenance.create')<x-btn-create :href="route('maintenance-schedules.create')" />@endcan
    </div>
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
            <td class="px-4 py-2.5 text-xs">{{ HumanLabel::label($item->type) }}</td>
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
