@extends('layouts.app')
@section('title', ' - PO Baru')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Purchase Order</h1>
<form method="POST" action="{{ route('purchase-orders.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Supplier</label>
            <select name="supplier_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Dari PR</label>
            <select name="purchase_request_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($prs as $pr)<option value="{{ $pr->id }}">{{ $pr->number }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="order_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div id="lines" class="mt-4 space-y-2">
        <div class="flex gap-2 line items-center">
            <select name="lines[0][item_id]" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm"><option value="">-- Item --</option>@foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->code }} - {{ $i->name }}</option>@endforeach</select>
            <input name="lines[0][qty]" type="number" step="0.0001" placeholder="Qty" oninput="calc(this)" class="w-24 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <input name="lines[0][unit_price]" type="number" step="0.01" placeholder="Harga" oninput="calc(this)" class="w-32 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <span class="line-total w-32 text-right text-sm font-medium">0</span>
        </div>
    </div>
    <button type="button" onclick="addLine()" class="mt-2 text-xs text-amber-600">+ Tambah baris</button>
    <div class="mt-4"><label class="text-xs font-semibold text-slate-600 uppercase">Biaya Lain (freight/admin)</label>
        <input name="other_cost" type="number" step="0.01" value="0" class="w-48 px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('purchase-orders.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@push('scripts')
<script>
let c = 1;
function addLine() {
    const el = document.querySelector('.line').cloneNode(true);
    el.querySelectorAll('select,input').forEach(i => { i.name = i.name.replace(/\[\d+\]/, '[' + c + ']'); if (i.tagName === 'INPUT') i.value = ''; });
    el.querySelector('.line-total').textContent = '0';
    document.getElementById('lines').appendChild(el);
    c++;
}
function calc(inp) {
    const line = inp.closest('.line');
    const qty = parseFloat(line.querySelector('input[name*="[qty]"]').value || 0);
    const price = parseFloat(line.querySelector('input[name*="[unit_price]"]').value || 0);
    line.querySelector('.line-total').textContent = (qty * price).toLocaleString('id-ID');
}
</script>
@endpush
@endsection