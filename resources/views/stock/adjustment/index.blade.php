@extends('layouts.app')
@section('title', ' - Penyesuaian Stok')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Penyesuaian Stok</h1>
    @can('create')<x-btn-create :href="route('stock-adjustments.create')" />@endcan
</div>
<x-filter-bar :route="route('stock-adjustments.index')">
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Tipe</th><th class="px-4 py-2.5">Gudang</th><th class="px-4 py-2.5">Alasan</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->adjustment_date?->format("d/m/Y") }}</td>
            <td class="px-4 py-2.5">{{ $item->type }}</td>
            <td class="px-4 py-2.5">{{ $item->warehouse?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ Str::limit($item->reason, 50) }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === "DRAFT")
                @can("stock.post")
                <form action="{{ route("stock-adjustments.post", $item) }}" method="POST" class="inline" onsubmit="return confirm(\"Posting penyesuaian?\")">@csrf
                <button class="text-xs text-indigo-600 hover:underline">Posting</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection