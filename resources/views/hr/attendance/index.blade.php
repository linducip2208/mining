@extends('layouts.app')
@section('title', ' - Absensi')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Absensi</h1>
    @can('attendance.create')
    <div class="flex gap-2">
        <form action="{{ route('attendances.import') }}" method="POST" enctype="multipart/form-data" class="flex gap-2">
            @csrf
            <input type="file" name="file" accept=".csv" class="text-xs" required>
            <button class="px-3 py-2 rounded-lg bg-slate-700 text-white text-xs">Import Fingerprint</button>
        </form>
        <a href="#manual" class="px-3 py-2 rounded-lg bg-amber-500 text-white text-xs font-semibold">Input Manual</a>
    </div>
    @endcan
</div>
<x-filter-bar :route="route('attendances.index')">
    <x-filter-input name="date" label="Tanggal" type="date" />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Karyawan</th><th class="px-4 py-2.5">Masuk</th>
        <th class="px-4 py-2.5">Keluar</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Telat (min)</th><th class="px-4 py-2.5 text-right">Lembur (jam)</th><th class="px-4 py-2.5">Sumber</th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $item->date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->employee?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->check_in }}</td>
            <td class="px-4 py-2.5">{{ $item->check_out }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->late_minutes, 0) }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->overtime_minutes / 60, 1) }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->source }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Tidak ada absensi tanggal ini</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>

<div id="manual" class="bg-white rounded-xl border border-slate-200 p-5 mt-4 max-w-xl">
    <h3 class="font-semibold text-sm mb-3">Input Manual</h3>
    <form method="POST" action="{{ route('attendances.store') }}" class="grid md:grid-cols-3 gap-3">
        @csrf
        <select name="employee_id" required class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">-- Karyawan --</option>@foreach (\App\Models\Employee::where('status', 'ACTIVE')->get() as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
        <input type="date" name="date" value="{{ $date }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm">
        <select name="status" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select>
        <input type="time" name="check_in" placeholder="Masuk" class="px-3 py-2 rounded-lg border border-slate-200 text-sm">
        <input type="time" name="check_out" placeholder="Keluar" class="px-3 py-2 rounded-lg border border-slate-200 text-sm">
        <button class="px-4 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
    </form>
</div>
@endsection