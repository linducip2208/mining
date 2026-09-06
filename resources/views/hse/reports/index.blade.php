@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp

@section('title', ' - Laporan HSE')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Laporan HSE</h1>
        <p class="text-sm text-slate-500">Insiden, near miss & laporan bahaya</p>
    </div>
    @can('hse.create')
    <x-btn-create label="Buat Laporan" :href="route('hse.reports.create')" />
    @endcan
</div>

<x-filter-bar :route="route('hse.reports.index')">
    <x-filter-input name="kind" label="Jenis" type="select" :options="['INCIDENT' => 'Insiden', 'NEAR_MISS' => 'Near Miss', 'HAZARD' => 'Bahaya']" />
    <x-filter-input name="status" label="Status" type="select" :options="['OPEN' => 'Terbuka', 'IN_PROGRESS' => 'Proses', 'CLOSED' => 'Tutup', 'CANCELLED' => 'Batal']" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Kejadian</th>
        <th class="px-4 py-2.5">Jenis</th>
        <th class="px-4 py-2.5">Lokasi</th>
        <th class="px-4 py-2.5">Severity</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('hse.reports.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5">{{ $item->occurred_at }}</td>
        <td class="px-4 py-2.5">{{ HumanLabel::label($item->kind) }}</td>
        <td class="px-4 py-2.5">{{ $item->location }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="in_array($item->severity, ['HIGH', 'CRITICAL', 'LTI', 'FATALITY']) ? 'BREAKDOWN' : 'PENDING'" /></td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
