@extends('layouts.app')

@section('title', ' - Buat Spesifikasi')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Spesifikasi Produk</h1>
</div>

<form method="POST" action="{{ route('specs.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-3 gap-4 mb-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Produk</label>
            <select name="item_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($items ?? [] as $it)
                    <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Customer (opsional)</label>
            <select name="customer_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">Umum</option>
                @foreach ($customers ?? [] as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site (opsional)</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Berlaku</label>
            <input type="date" name="effective_date" value="{{ old('effective_date', today()->toDateString()) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kedaluwarsa</label>
            <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan (opsional)</label>
            <select name="company_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="text-sm font-bold text-slate-700 mb-2">Batas Parameter</div>
    <div id="spec-lines" class="space-y-2">
        @foreach ($parameters ?? [] as $p)
        <div class="grid grid-cols-5 gap-2 items-center bg-slate-50 rounded-lg px-3 py-2">
            <label class="text-xs font-semibold flex items-center gap-1.5 col-span-2">
                <input type="checkbox" name="lines[{{ $p->id }}][quality_parameter_id]" value="{{ $p->id }}" class="rounded"> {{ $p->code }} — {{ $p->name }} ({{ $p->unit }})
            </label>
            <input type="number" step="any" name="lines[{{ $p->id }}][min_value]" placeholder="Min" class="px-2 py-1.5 rounded border border-slate-200 text-sm">
            <input type="number" step="any" name="lines[{{ $p->id }}][max_value]" placeholder="Max" class="px-2 py-1.5 rounded border border-slate-200 text-sm">
            <input type="number" step="any" name="lines[{{ $p->id }}][target_value]" placeholder="Target" class="px-2 py-1.5 rounded border border-slate-200 text-sm">
        </div>
        @endforeach
    </div>
    <p class="text-[11px] text-slate-400 mt-2">Centang parameter yang dipakai. Baris tanpa centang akan ditolak validasi — hapus manual bila perlu.</p>

    <script>
    document.querySelector('form').addEventListener('submit', function () {
        document.querySelectorAll('#spec-lines > div').forEach(function (row) {
            var cb = row.querySelector('input[type=checkbox]');
            if (!cb || !cb.checked) {
                row.querySelectorAll('input').forEach(function (i) { i.removeAttribute('name'); });
            }
        });
    });
    </script>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('specs.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
