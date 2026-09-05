@extends('layouts.app')

@section('title', ' - Item')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Item</h1>
</div>

<form method="POST" action="{{ $item ? route('items.update', $item) : route('items.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
                    <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
                    <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kategori</label>
                    <select name="item_category_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach ($itemCategories ?? [] as $id => $n)
                            <option value="{{ $id }}" @selected($item->item_category_id ?? old('item_category_id'))>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
                    <select name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        <option value="PRODUCT" @selected($item->type ?? old('type') == 'PRODUCT')>Produk</option>
                        <option value="RAW" @selected($item->type ?? old('type') == 'RAW')>Bahan Baku</option>
                        <option value="SPAREPART" @selected($item->type ?? old('type') == 'SPAREPART')>Sparepart</option>
                        <option value="CONSUMABLE" @selected($item->type ?? old('type') == 'CONSUMABLE')>Consumable</option>
                        <option value="FUEL" @selected($item->type ?? old('type') == 'FUEL')>BBM</option>
                        <option value="GENERAL" @selected($item->type ?? old('type') == 'GENERAL')>Umum</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Satuan</label>
                    <select name="unit_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach ($units ?? [] as $id => $n)
                            <option value="{{ $id }}" @selected($item->unit_id ?? old('unit_id'))>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Stok Minimum</label>
                    <input type="number" name="min_stock" value="{{ $item->min_stock ?? old('min_stock') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Reorder Point</label>
                    <input type="number" name="reorder_point" value="{{ $item->reorder_point ?? old('reorder_point') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Harga Standar</label>
                    <input type="number" name="standard_cost" value="{{ $item->standard_cost ?? old('standard_cost') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Deskripsi</label>
                    <textarea name="description" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">{{ $item->description ?? old('description') }}</textarea>
                </div>
                <label class="flex items-center gap-2 md:col-span-2">
                    <input type="checkbox" name="status" value="1" @checked(old('status', $item->status ?? true)) class="rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                    <span class="text-sm">Aktif</span>
                </label>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('items.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection