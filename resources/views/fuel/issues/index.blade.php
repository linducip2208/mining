@extends('layouts.app')

@section('title', ' - Issue BBM')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Issue BBM</h1>
        <p class="text-sm text-slate-500">Pengeluaran solar ke unit / kendaraan</p>
    </div>
    @can('fuel.create')
    <x-btn-create label="Buat Issue" :href="route('fuel-issues.create')" />
    @endcan
</div>

@if (isset($issue))
<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-4 text-sm">
    <div class="font-bold text-indigo-800">{{ $issue->number }} <x-status-badge :status="$issue->status" /></div>
    <div class="text-indigo-700 mt-1">{{ number_format($issue->liter, 1) }} L · {{ $issue->tank?->code }} → {{ $issue->equipment?->code ?? $issue->vehicle_plate }} · L/H {{ $issue->liter_per_hour }} ({{ $issue->variance_status ?? '—' }})</div>
    <div class="flex gap-2 mt-2">
        @can('fuel.create')
        @if ($issue->status === 'DRAFT')
        <form method="POST" action="{{ route('fuel-issues.approve', $issue) }}">@csrf<button class="px-4 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-semibold">Ajukan Approval</button></form>
        @endif
        @endcan
        @can('fuel.post')
        @if ($issue->status === 'APPROVED')
        <form method="POST" action="{{ route('fuel-issues.post', $issue) }}">@csrf<button class="px-4 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">Posting</button></form>
        @endif
        @endcan
    </div>
</div>
@endif

<x-filter-bar :route="route('fuel-issues.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor..." />
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'APPROVED' => 'Disetujui', 'POSTED' => 'Posted', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Tangki</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5 text-right">Liter</th>
        <th class="px-4 py-2.5 text-right">Biaya</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->issue_date }}</td>
        <td class="px-4 py-2.5"><a href="{{ route('fuel-issues.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5">{{ $item->tank?->code }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->equipment?->code ?? $item->vehicle_plate }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->liter, 1) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->total_cost, 0) }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
