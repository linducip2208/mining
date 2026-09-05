@extends('layouts.app')
@section('title', ' - Statement Deposit')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Statement Deposit</h1>
        <p class="text-sm text-slate-500">{{ $customer->name }} ({{ $customer->code }})</p>
    </div>
    <div class="text-right">
        <div class="text-xs text-slate-400">Saldo Saat Ini</div>
        <div class="text-2xl font-bold text-green-700">Rp {{ number_format($balance, 0, ',', '.') }}</div>
    </div>
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Tipe</th><th class="px-4 py-2.5">Referensi</th>
        <th class="px-4 py-2.5 text-right">Masuk</th><th class="px-4 py-2.5 text-right">Keluar</th><th class="px-4 py-2.5 text-right">Saldo</th>
    </x-slot:head>
    <tbody>
        @php $run = 0; @endphp
        @forelse ($ledger as $row)
        <tr>
            @php
                $in = in_array($row->movement_type, ['DEPOSIT_IN']) ? $row->amount : 0;
                $out = in_array($row->movement_type, ['DEPOSIT_USED', 'DEPOSIT_REFUND']) ? $row->amount : 0;
                $run += $in - $out;
            @endphp
            <td class="px-4 py-2.5">{{ $row->deposit_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$row->movement_type" /></td>
            <td class="px-4 py-2.5 text-xs">{{ $row->ref_number }}</td>
            <td class="px-4 py-2.5 text-right text-green-600">{{ $in > 0 ? number_format($in, 0, ',', '.') : '' }}</td>
            <td class="px-4 py-2.5 text-right text-red-600">{{ $out > 0 ? number_format($out, 0, ',', '.') : '' }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">Rp {{ number_format($run, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi</td></tr>
        @endforelse
    </tbody>
</x-table>
@endsection