@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp

@section('title', ' - Laporan Pemeliharaan')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Pemeliharaan</h1>
    <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
</div>
<x-filter-bar :route="route('report.maintenance')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="grid lg:grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Biaya Pemeliharaan per Alat</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($costByEquipment as $name => $cost)
                <tr><td class="py-1.5">{{ $name }}</td><td class="py-1.5 text-right font-semibold">Rp {{ number_format($cost, 0, ',', '.') }}</td></tr>
                @empty
                <tr><td class="py-4 text-center text-slate-400" colspan="2">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Downtime per Alat (Jam)</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($downtimeByEquipment as $name => $hours)
                <tr><td class="py-1.5">{{ $name }}</td><td class="py-1.5 text-right font-semibold {{ $hours > 0 ? 'text-orange-600' : '' }}">{{ number_format($hours, 1) }}</td></tr>
                @empty
                <tr><td class="py-4 text-center text-slate-400" colspan="2">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold text-sm mb-3">Work Order ({{ $workOrders->count() }})</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b">
            <th class="py-2">Nomor</th><th class="py-2">Tanggal</th><th class="py-2">Alat/Aset</th><th class="py-2">Tipe</th>
            <th class="py-2 text-right">Downtime</th><th class="py-2 text-right">Biaya</th><th class="py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($workOrders as $wo)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ $wo->number }}</td>
                <td class="py-1.5">{{ $wo->date?->format('d/m/Y') }}</td>
                <td class="py-1.5">{{ $wo->equipment?->name ?? $wo->asset?->name ?? '-' }}</td>
                <td class="py-1.5 text-xs">{{ HumanLabel::label($wo->type) }}</td>
                <td class="py-1.5 text-right">{{ number_format($wo->downtime_hours, 1) }}</td>
                <td class="py-1.5 text-right">Rp {{ number_format($wo->actual_cost, 0, ',', '.') }}</td>
                <td class="py-1.5"><x-status-badge :status="$wo->status" /></td>
            </tr>
            @empty
            <tr><td colspan="7" class="py-6 text-center text-slate-400">Tidak ada WO pada periode ini</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
