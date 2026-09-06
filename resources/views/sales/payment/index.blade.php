@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp
@section('title', ' - Pembayaran')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Penerimaan Pembayaran</h1>
    @can('payment.create')<x-btn-create :href="route('payments.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Customer</th>
        <th class="px-4 py-2.5">Metode</th><th class="px-4 py-2.5 text-right">Jumlah</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->payment_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->customer?->name ?? $item->supplier?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ HumanLabel::label($item->method) }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5"></td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada pembayaran</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
