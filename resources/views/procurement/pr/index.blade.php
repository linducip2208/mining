@extends('layouts.app')
@section('title', ' - Permintaan Pembelian')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Purchase Request</h1>
    @can('purchase_request.create')<x-btn-create :href="route('purchase-requests.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Item</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->request_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->items->map(fn($i) => $i->item?->name . ' (' . number_format($i->qty, 2) . ')')->implode(', ') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'SUBMITTED')
                @can('purchase_request.approve')
                <form action="{{ route('purchase-requests.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada PR</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection