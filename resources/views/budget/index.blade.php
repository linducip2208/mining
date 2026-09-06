@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp

@section('title', ' - Budget')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Budget OPEX / CAPEX</h1>
        <p class="text-sm text-slate-500">Pagu per COA — PR/PO otomatis cek sisa budget</p>
    </div>
    @can('budget.create')
    <x-btn-create label="Buat Budget" :href="route('budgets.create')" />
    @endcan
</div>

<x-filter-bar :route="route('budgets.index')">
    <x-filter-input name="year" label="Tahun" placeholder="2026" />
    <x-filter-input name="type" label="Tipe" type="select" :options="['OPEX' => 'OPEX', 'CAPEX' => 'CAPEX']" />
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'APPROVED' => 'Disetujui', 'REVISED' => 'Revisi', 'CLOSED' => 'Tutup', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Tahun</th>
        <th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5">Perusahaan / Site</th>
        <th class="px-4 py-2.5 text-right">Versi</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('budgets.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5">{{ $item->year }}</td>
        <td class="px-4 py-2.5">{{ HumanLabel::label($item->type) }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->company?->name }} / {{ $item->site?->name ?? 'Pusat' }}</td>
        <td class="px-4 py-2.5 text-right">v{{ $item->version }}</td>
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
