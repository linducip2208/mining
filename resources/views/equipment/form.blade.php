@extends('layouts.app')

@section('title', ' - Peralatan')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Peralatan</h1>
</div>

<form method="POST" action="{{ $item ? route('equipment.update', $item) : route('equipment.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
                    <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach ($companies ?? [] as $id => $n)
                            <option value="{{ $id }}" @selected($item->company_id ?? old('company_id'))>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
                    <select name="site_id"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach ($sites ?? \App\Models\Site::all() as $s)
                            <option value="{{ $s->id ?? $s }}" @selected($item->site_id ?? old('site_id'))>{{ $s->name ?? $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
                    <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
                    <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
                    <select name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        <option value="EXCAVATOR" @selected($item->type ?? old('type') == 'EXCAVATOR')>Excavator</option>
                        <option value="LOADER" @selected($item->type ?? old('type') == 'LOADER')>Loader</option>
                        <option value="DUMP_TRUCK" @selected($item->type ?? old('type') == 'DUMP_TRUCK')>Dump Truck</option>
                        <option value="CRUSHER" @selected($item->type ?? old('type') == 'CRUSHER')>Crusher</option>
                        <option value="DOZER" @selected($item->type ?? old('type') == 'DOZER')>Dozer</option>
                        <option value="GRADER" @selected($item->type ?? old('type') == 'GRADER')>Grader</option>
                        <option value="DRILL" @selected($item->type ?? old('type') == 'DRILL')>Drill</option>
                        <option value="GENSET" @selected($item->type ?? old('type') == 'GENSET')>Genset</option>
                        <option value="OTHER" @selected($item->type ?? old('type') == 'OTHER')>Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Merek</label>
                    <input type="text" name="brand" value="{{ $item->brand ?? old('brand') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Model</label>
                    <input type="text" name="model" value="{{ $item->model ?? old('model') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">No Seri</label>
                    <input type="text" name="serial_no" value="{{ $item->serial_no ?? old('serial_no') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">No Polisi</label>
                    <input type="text" name="plate_no" value="{{ $item->plate_no ?? old('plate_no') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kepemilikan</label>
                    <select name="ownership" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        <option value="OWNED" @selected($item->ownership ?? old('ownership') == 'OWNED')>Milik</option>
                        <option value="RENTED" @selected($item->ownership ?? old('ownership') == 'RENTED')>Sewa</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
                    <select name="status" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        <option value="AVAILABLE" @selected($item->status ?? old('status') == 'AVAILABLE')>Tersedia</option>
                        <option value="IN_USE" @selected($item->status ?? old('status') == 'IN_USE')>Dipakai</option>
                        <option value="MAINTENANCE" @selected($item->status ?? old('status') == 'MAINTENANCE')>Pemeliharaan</option>
                        <option value="BREAKDOWN" @selected($item->status ?? old('status') == 'BREAKDOWN')>Rusak</option>
                        <option value="RETIRED" @selected($item->status ?? old('status') == 'RETIRED')>Dipensiunkan</option>
                    </select>
                </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('equipment.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection