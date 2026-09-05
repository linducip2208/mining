@extends('layouts.app')

@section('title', ' - Aset')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Aset</h1>
</div>

<form method="POST" action="{{ $item ? route('assets.update', $item) : route('assets.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
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
                        <option value="EQUIPMENT" @selected($item->type ?? old('type') == 'EQUIPMENT')>Peralatan</option>
                        <option value="VEHICLE" @selected($item->type ?? old('type') == 'VEHICLE')>Kendaraan</option>
                        <option value="MACHINE" @selected($item->type ?? old('type') == 'MACHINE')>Mesin</option>
                        <option value="CRUSHER" @selected($item->type ?? old('type') == 'CRUSHER')>Crusher</option>
                        <option value="BUILDING" @selected($item->type ?? old('type') == 'BUILDING')>Gedung</option>
                        <option value="LAND" @selected($item->type ?? old('type') == 'LAND')>Tanah</option>
                        <option value="OTHER" @selected($item->type ?? old('type') == 'OTHER')>Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">No Seri</label>
                    <input type="text" name="serial_no" value="{{ $item->serial_no ?? old('serial_no') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Tgl Perolehan</label>
                    <input type="date" name="acquisition_date" value="{{ $item->acquisition_date ?? old('acquisition_date') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Harga Perolehan</label>
                    <input type="number" name="acquisition_cost" value="{{ $item->acquisition_cost ?? old('acquisition_cost') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Umur Ekonomis (Thn)</label>
                    <input type="number" name="useful_life_years" value="{{ $item->useful_life_years ?? old('useful_life_years') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Penanggung Jawab</label>
                    <select name="employee_id"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach ($employees ?? [] as $id => $n)
                            <option value="{{ $id }}" @selected($item->employee_id ?? old('employee_id'))>{{ $n }}</option>
                        @endforeach
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
                        <option value="DISPOSED" @selected($item->status ?? old('status') == 'DISPOSED')>Dibuang</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">{{ $item->notes ?? old('notes') }}</textarea>
                </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('assets.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection