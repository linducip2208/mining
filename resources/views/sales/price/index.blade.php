@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp
@section('title', ' - Daftar Harga')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Daftar Harga</h1>
    @can('price.create')<x-btn-create :href="route('price-lists.create')" />@endcan
</div>
<x-filter-bar :route="route('price-lists.index')">
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="type" label="Tipe" type="select" :options="array_combine($types, $types)" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th><th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5">Customer/Site</th><th class="px-4 py-2.5">Efektif</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->code }}</td>
            <td class="px-4 py-2.5">{{ $item->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ HumanLabel::label($item->type) }}</td>
            <td class="px-4 py-2.5">{{ $item->customer?->name ?? $item->site?->name ?? 'Semua' }}</td>
            <td class="px-4 py-2.5">{{ $item->effective_date?->format('d/m/Y') }}{{ $item->expiry_date ? ' - ' . $item->expiry_date->format('d/m/Y') : '' }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'DRAFT')
                @can('price.approve')
                <form action="{{ route('price-lists.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada daftar harga</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
