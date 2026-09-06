@extends('layouts.app')

@section('title', ' - Permit Kerja')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Permit Kerja (Work Permit)</h1>
    <p class="text-sm text-slate-500">Izin kerja berbahaya: confined space, ketinggian, hot work, listrik</p>
</div>

@can('hse.create')
<form method="POST" action="{{ route('hse.permits.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="grid md:grid-cols-4 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? [] as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jenis Pekerjaan</label>
            <input type="text" name="work_type" required maxlength="100" placeholder="Hot work / confined..." class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Lokasi</label>
            <input type="text" name="location" maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Berlaku Dari</label>
            <input type="date" name="valid_from" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Sampai</label>
            <input type="date" name="valid_until" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Pemohon</label>
            <select name="requester_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($employees ?? [] as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Unit</label>
            <select name="equipment_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($units ?? [] as $u)
                    <option value="{{ $u->id }}">{{ $u->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-4">
            <label class="text-xs font-semibold text-slate-600 uppercase">Tindakan Pencegahan</label>
            <input type="text" name="precautions" maxlength="2000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Buat Permit</button>
</form>
@endcan

<x-filter-bar :route="route('hse.permits.index')">
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'APPROVED' => 'Disetujui', 'ACTIVE' => 'Aktif', 'EXPIRED' => 'Kedaluwarsa', 'CLOSED' => 'Tutup', 'REJECTED' => 'Ditolak']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Pekerjaan</th>
        <th class="px-4 py-2.5">Lokasi</th>
        <th class="px-4 py-2.5">Berlaku</th>
        <th class="px-4 py-2.5">Pemohon</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5 font-mono">{{ $item->number }}</td>
        <td class="px-4 py-2.5">{{ $item->work_type }}</td>
        <td class="px-4 py-2.5">{{ $item->location }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->valid_from }} → {{ $item->valid_until }}</td>
        <td class="px-4 py-2.5">{{ $item->requester?->name ?? '—' }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right">
            @can('hse.create')
            @if ($item->status === 'DRAFT')
            <form method="POST" action="{{ route('hse.permits.approve', $item) }}" class="inline">@csrf<button class="text-green-600 hover:underline text-xs">Ajukan</button></form>
            @endif
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
