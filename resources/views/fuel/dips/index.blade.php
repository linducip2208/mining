@extends('layouts.app')

@section('title', ' - Dip Tangki')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Dip Tangki (Sounding)</h1>
    <p class="text-sm text-slate-500">Ukur fisik vs sistem — selisih tercatat otomatis</p>
</div>

@can('fuel.create')
<form method="POST" action="{{ route('fuel-dips.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="grid md:grid-cols-5 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tangki</label>
            <select name="fuel_tank_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($tanks ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="dip_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Dip (cm)</label>
            <input type="number" step="0.01" min="0" name="dip_cm" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Fisik (Liter)</label>
            <input type="number" step="0.01" min="0" name="physical_liter" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
            <input type="text" name="notes" maxlength="1000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Dip</button>
</form>
@endcan

<x-filter-bar :route="route('fuel-dips.index')">
    <x-filter-input name="fuel_tank_id" label="Tangki" type="select" :options="$tanks ?? []" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Tangki</th>
        <th class="px-4 py-2.5 text-right">Dip (cm)</th>
        <th class="px-4 py-2.5 text-right">Fisik (L)</th>
        <th class="px-4 py-2.5 text-right">Sistem (L)</th>
        <th class="px-4 py-2.5 text-right">Selisih (L)</th>
        <th class="px-4 py-2.5 text-right">Selisih %</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->dip_date }}</td>
        <td class="px-4 py-2.5">{{ $item->tank?->code }}</td>
        <td class="px-4 py-2.5 text-right">{{ $item->dip_cm }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->physical_liter, 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->system_liter, 1) }}</td>
        <td class="px-4 py-2.5 text-right font-semibold {{ ($item->variance ?? 0) < 0 ? 'text-red-600' : 'text-slate-700' }}">{{ number_format($item->variance, 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $item->variance_pct ?? 0 }}%</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status ?? 'NORMAL'" /></td>
        <td class="px-4 py-2.5 text-right">
            @can('fuel.approve')
            @if (($item->status ?? '') === 'PENDING')
            <form method="POST" action="{{ route('fuel-dips.approve', $item) }}" class="inline">@csrf<button class="text-green-600 hover:underline text-xs">Approve</button></form>
            @endif
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
