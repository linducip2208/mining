@extends('layouts.app')
@section('title', ' - Insentif Operator')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Insentif Operator</h1>
    @can('incentive.create')<x-btn-create :href="route('operator-incentives.create')" />@endcan
</div>
<p class="text-xs text-slate-400 mb-3">Insentif wajib APPROVED sebelum dapat masuk payroll.</p>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Karyawan</th><th class="px-4 py-2.5">Basis</th>
        <th class="px-4 py-2.5">Periode</th><th class="px-4 py-2.5 text-right">Qty</th><th class="px-4 py-2.5 text-right">Rate</th>
        <th class="px-4 py-2.5 text-right">Jumlah</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->employee?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->basis }}</td>
            <td class="px-4 py-2.5">{{ $item->period }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->quantity, 2) }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->rate, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'DRAFT')
                @can('incentive.approve')
                <form action="{{ route('operator-incentives.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada insentif</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection