@extends('layouts.app')
@section('title', ' - Tagihan Vendor')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Tagihan Vendor</h1>
<form method="POST" action="{{ route('vendor-bills.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Supplier</label>
            <select name="supplier_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">No Invoice Supplier</label>
            <input name="supplier_invoice_no" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal Tagihan</label>
            <input type="date" name="bill_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Jatuh Tempo</label>
            <input type="date" name="due_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div id="lines" class="mt-4 space-y-2">
        <div class="flex gap-2 line items-center">
            <select name="lines[0][item_id]" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm"><option value="">-- Item (opsional) --</option>@foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select>
            <input name="lines[0][description]" placeholder="Deskripsi (jika non-item)" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <input name="lines[0][qty]" type="number" step="0.0001" value="1" placeholder="Qty" class="w-20 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <input name="lines[0][unit_price]" type="number" step="0.01" placeholder="Harga" class="w-32 px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
    </div>
    <button type="button" onclick="addLine()" class="mt-2 text-xs text-amber-600">+ Tambah baris</button>
    <p class="text-xs text-slate-400 mt-2">Baris tanpa item = beban administrasi. Baris dengan item = inventory cost (terpisah).</p>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('vendor-bills.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
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