@extends('layouts.app')

@section('title', ' - Titik Bongkar')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Titik Bongkar</h1>
</div>

<form method="POST" action="{{ $item ? route('dumping-points.update', $item) : route('dumping-points.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? \App\Models\Site::orderBy('name')->get() as $s)
                    <option value="{{ $s->id ?? $s }}" @selected(($item->site_id ?? old('site_id')) == ($s->id ?? $s))>{{ $s->name ?? $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                @foreach (['STOCKPILE' => 'Stockpile', 'CRUSHER' => 'Crusher', 'PORT' => 'Port', 'WASTE_DUMP' => 'Waste Dump'] as $v => $l)
                    <option value="{{ $v }}" @selected(($item->type ?? old('type')) == $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required maxlength="150" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Gudang (opsional)</label>
            <select name="warehouse_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="">-- Tidak ada --</option>
                @foreach (\App\Models\Warehouse::orderBy('name')->get() as $w)
                    <option value="{{ $w->id }}" @selected(($item->warehouse_id ?? old('warehouse_id')) == $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Crusher (opsional)</label>
            <select name="crusher_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="">-- Tidak ada --</option>
                @foreach (\App\Models\Crusher::orderBy('name')->get() as $c)
                    <option value="{{ $c->id }}" @selected(($item->crusher_id ?? old('crusher_id')) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
            <select name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="1" @selected(($item->status ?? old('status', 1)) == 1)>Aktif</option>
                <option value="0" @selected(($item->status ?? old('status')) == 0)>Nonaktif</option>
            </select>
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('dumping-points.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
