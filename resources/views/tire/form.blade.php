@extends('layouts.app')

@section('title', ' - Registrasi Ban')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Registrasi Ban</h1>
</div>

<form method="POST" action="{{ route('tires.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}" @selected(old('company_id') == $id)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">No Seri</label>
            <input type="text" name="serial_no" value="{{ old('serial_no') }}" required maxlength="50" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Merek</label>
            <input type="text" name="brand" value="{{ old('brand') }}" maxlength="100" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Ukuran</label>
            <input type="text" name="size" value="{{ old('size') }}" maxlength="50" placeholder="24.00-35" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Pattern</label>
            <input type="text" name="pattern" value="{{ old('pattern') }}" maxlength="50" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Biaya Beli (Rp)</label>
            <input type="number" step="0.01" min="0" name="purchase_cost" value="{{ old('purchase_cost') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal Beli</label>
            <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('tires.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
