@extends('layouts.app')
@section('title', ' - Selisih Harga')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Selisih Harga (Reference vs Realization)</h1>
<x-filter-bar :route="route('price_variance.index')">
    <x-filter-input name="approval_status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Item</th><th class="px-4 py-2.5 text-right">Harga Referensi</th>
        <th class="px-4 py-2.5 text-right">Harga Realisasi</th><th class="px-4 py-2.5 text-right">Qty</th>
        <th class="px-4 py-2.5 text-right">Variance</th><th class="px-4 py-2.5 text-right">%</th><th class="px-4 py-2.5">Tipe</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->item?->name }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->reference_price, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->realization_price, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->quantity, 2) }}</td>
            <td class="px-4 py-2.5 text-right font-semibold {{ $item->variance_type === 'FAVORABLE' ? 'text-green-600' : 'text-red-600' }}">
                Rp {{ number_format($item->variance, 0, ',', '.') }}
            </td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->variance_percentage, 1) }}%</td>
            <td class="px-4 py-2.5"><span class="text-xs {{ $item->variance_type === 'FAVORABLE' ? 'text-green-700' : 'text-red-700' }}">{{ $item->variance_type === 'FAVORABLE' ? 'Favorable' : 'Unfavorable' }}</span></td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->approval_status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->approval_status === 'PENDING')
                @can('price_variance.approve')
                <form action="{{ route('price-variances.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="10" class="px-4 py-10 text-center text-slate-400">Tidak ada data selisih harga</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
<p class="text-xs text-slate-400 mt-3">Catatan: selisih harga bersifat informasional. Makna akuntansinya (laba/rugi atau rekonsiliasi) ditentukan oleh konfigurasi mapping akun.</p>
@endsection