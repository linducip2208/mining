@extends('layouts.app')
@section('title', ' - Payroll')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Payroll</h1>
    @can('payroll.create')<x-btn-create label="Run Baru" :href="route('payroll-runs.create')" />@endcan
</div>

@if ($payrollRun)
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="font-bold text-slate-800">{{ $payrollRun->number }} · {{ $payrollRun->period }}</div>
            <x-status-badge :status="$payrollRun->status" class="mt-1" />
        </div>
        <div class="flex gap-2">
            @if ($payrollRun->status === 'DRAFT')
            @can('payroll.update')
            <form action="{{ route('payroll.calculate', $payroll_run ?? $payrollRun) }}" method="POST">@csrf
                <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Kalkulasi</button></form>
            @endcan
            @endif
            @if ($payrollRun->status === 'CALCULATED')
            @can('payroll.approve')
            <form action="{{ route('payroll.approve', $payrollRun) }}" method="POST">@csrf
                <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm">Setujui</button></form>
            @endcan
            @endif
            @if ($payrollRun->status === 'APPROVED')
            @can('payroll.post')
            <form action="{{ route('payroll.post', $payrollRun) }}" method="POST">@csrf
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm">Posting Jurnal</button></form>
            @endcan
            @endif
            @if ($payrollRun->status === 'POSTED')
            @can('payroll.post')
            <form action="{{ route('payroll.pay', $payrollRun) }}" method="POST">@csrf
                <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm">Tandai Dibayar</button></form>
            @endcan
            @endif
        </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
        <x-stat-card title="Karyawan" :value="$payrollRun->employee_count" color="slate" />
        <x-stat-card title="Total Bruto" :value="'Rp ' . number_format($payrollRun->total_gross, 0, ',', '.')" color="blue" />
        <x-stat-card title="Total Potongan" :value="'Rp ' . number_format($payrollRun->total_deduction, 0, ',', '.')" color="red" />
        <x-stat-card title="Total Neto" :value="'Rp ' . number_format($payrollRun->total_net, 0, ',', '.')" color="green" />
    </div>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Karyawan</th><th class="px-4 py-2.5 text-right">Gaji Pokok</th>
        <th class="px-4 py-2.5 text-right">Tunjangan+Lain</th><th class="px-4 py-2.5 text-right">Potongan</th>
        <th class="px-4 py-2.5 text-right">Neto</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($payrollRun->details as $d)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $d->employee?->name }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($d->basic_salary, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format(collect($d->components['earnings'] ?? [])->where('code', '!=', 'BASIC')->sum('amount'), 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($d->total_deduction, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right font-bold">Rp {{ number_format($d->net_salary, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><a href="{{ route('payroll.payslip', [$payrollRun, $d]) }}" target="_blank" class="text-amber-600 text-xs hover:underline">Slip</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada detail. Jalankan kalkulasi.</td></tr>
        @endforelse
    </tbody>
</x-table>
@endif

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Periode</th><th class="px-4 py-2.5">Perusahaan</th>
        <th class="px-4 py-2.5 text-right">Neto</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->period }}</td>
            <td class="px-4 py-2.5">{{ $item->company?->name }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->total_net, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5"><a href="{{ route('payroll-runs.show', $item) }}" class="text-amber-600 text-xs hover:underline">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada payroll run</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection