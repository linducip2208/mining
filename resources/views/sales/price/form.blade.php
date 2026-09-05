@extends('layouts.app')
@section('title', ' - Daftar Harga')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Daftar Harga</h1>
<form method="POST" action="{{ route('price-lists.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input name="code" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input name="name" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Customer</label>
            <select name="customer_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($sites as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Efektif Dari</label>
            <input type="date" name="effective_date" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Sampai</label>
            <input type="date" name="expiry_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div id="lines" class="mt-4 space-y-2">
        <div class="flex gap-2 line items-center">
            <select name="lines[0][item_id]" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm"><option value="">-- Item --</option>@foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->code }} - {{ $i->name }}</option>@endforeach</select>
            <input name="lines[0][price]" type="number" step="0.01" placeholder="Harga" class="w-36 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <input name="lines[0][min_qty]" type="number" step="0.0001" placeholder="Min Qty" class="w-28 px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
    </div>
    <button type="button" onclick="addLine()" class="mt-2 text-xs text-amber-600">+ Tambah baris</button>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('price-lists.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@push('scripts')
<script>
let c = 1;
function addLine() {
    const el = document.querySelector('.line').cloneNode(true);
    el.querySelectorAll('select,input').forEach(i => { i.name = i.name.replace(/\[\d+\]/, '[' + c + ']'); if (i.tagName === 'INPUT') i.value = ''; });
    document.getElementById('lines').appendChild(el);
    c++;
}
</script>
@endpush
@endsection