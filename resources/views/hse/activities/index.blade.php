@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp

@section('title', ' - Kegiatan K3')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Kegiatan K3</h1>
    <p class="text-sm text-slate-500">Inspeksi, toolbox meeting, training & cek APD</p>
</div>

@can('hse.create')
<form method="POST" action="{{ route('hse.activities.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
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
                @foreach ($sites ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jenis</label>
            <select name="kind" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                @foreach ($kinds ?? ['INSPECTION' => 'Inspeksi', 'TOOLBOX' => 'Toolbox Meeting', 'TRAINING' => 'Training', 'PPE_CHECK' => 'Cek APD'] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="activity_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-slate-600 uppercase">Topik</label>
            <input type="text" name="topic" required maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Peserta</label>
            <input type="text" name="participants" maxlength="2000" placeholder="cth: 25 orang shift 1" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Hasil</label>
            <input type="text" name="result" maxlength="2000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Catat Kegiatan</button>
</form>
@endcan

<x-filter-bar :route="route('hse.activities.index')">
    <x-filter-input name="kind" label="Jenis" type="select" :options="['INSPECTION' => 'Inspeksi', 'TOOLBOX' => 'Toolbox Meeting', 'TRAINING' => 'Training', 'PPE_CHECK' => 'Cek APD']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Jenis</th>
        <th class="px-4 py-2.5">Topik</th>
        <th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Peserta</th>
        <th class="px-4 py-2.5">Hasil</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->activity_date }}</td>
        <td class="px-4 py-2.5">{{ $kinds[$item->kind] ?? HumanLabel::label($item->kind) }}</td>
        <td class="px-4 py-2.5">{{ $item->topic }}</td>
        <td class="px-4 py-2.5">{{ $item->site?->name ?? '—' }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->participants }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->result }}</td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
