@extends('layouts.app')

@section('title', ' - Laporan HR')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan HR & Absensi</h1>
    <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
</div>
<x-filter-bar :route="route('report.hr')" class="print:hidden">
    <x-filter-input name="period" label="Periode (YYYY-MM)" />
</x-filter-bar>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Rekap Absensi per Karyawan — Periode {{ $month }}</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b">
            <th class="py-2">Karyawan</th><th class="py-2 text-right">Hadir</th><th class="py-2 text-right">Telat</th><th class="py-2 text-right">Jam Lembur</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($attendanceSummary as $row)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ $row->name }}</td>
                <td class="py-1.5 text-right">{{ $row->present }}</td>
                <td class="py-1.5 text-right {{ $row->late > 0 ? 'text-orange-600' : '' }}">{{ $row->late }}</td>
                <td class="py-1.5 text-right">{{ number_format($row->overtime_hours, 1) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-6 text-center text-slate-400">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($payrolls->isNotEmpty())
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold text-sm mb-3">Payroll Periode {{ $month }}</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b">
            <th class="py-2">Nomor</th><th class="py-2">Perusahaan</th><th class="py-2 text-right">Karyawan</th>
            <th class="py-2 text-right">Bruto</th><th class="py-2 text-right">Potongan</th><th class="py-2 text-right">Neto</th><th class="py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($payrolls as $p)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ $p->number }}</td>
                <td class="py-1.5">{{ $p->company?->name }}</td>
                <td class="py-1.5 text-right">{{ $p->employee_count }}</td>
                <td class="py-1.5 text-right">Rp {{ number_format($p->total_gross, 0, ',', '.') }}</td>
                <td class="py-1.5 text-right">Rp {{ number_format($p->total_deduction, 0, ',', '.') }}</td>
                <td class="py-1.5 text-right font-bold">Rp {{ number_format($p->total_net, 0, ',', '.') }}</td>
                <td class="py-1.5"><x-status-badge :status="$p->status" /></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
