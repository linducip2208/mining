@extends('layouts.app')
@section('title', ' - Surat Jalan')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Surat Jalan</h1>
<form method="POST" action="{{ route('delivery-orders.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Sales Order</label>
            <select name="sales_order_id" required onchange="filterItems(this.value)" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">-- Pilih SO --</option>
                @foreach ($salesOrders as $so)<option value="{{ $so->id }}" data-items='@json($so->items->where("qty_delivered", "<", DB::raw("qty"))->map(fn($i) => ["id" => $i->item_id, "name" => $i->item?->name, "remaining" => (float)$i->qty - (float)$i->qty_delivered]))' @selected(old('sales_order_id') == $so->id)>{{ $so->number }} - {{ $so->customer?->name }}</option>@endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Gudang</label>
            <select name="warehouse_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($warehouses as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="delivery_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">No Polisi</label>
            <input name="vehicle_plate" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm uppercase"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Pengemudi</label>
            <input name="driver_name" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div id="lines" class="mt-4 space-y-2">
        <div class="flex gap-2 line items-center">
            <select name="lines[0][item_id]" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm"><option value="">-- Pilih SO dulu --</option></select>
            <input name="lines[0][qty_ordered]" type="number" step="0.0001" placeholder="Qty" class="w-32 px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
    </div>
    <button type="button" onclick="addLine()" class="mt-2 text-xs text-amber-600">+ Tambah baris</button>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('delivery-orders.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
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
function filterItems(soId) {
    const opt = document.querySelector('option[value="' + soId + '"]');
    if (!opt || !opt.dataset.items) return;
    const items = JSON.parse(opt.dataset.items);
    document.getElementById('lines').innerHTML = '';
    window.__items = items;
    addFirst();
}
function addFirst() {
    const el = document.querySelector('.line') || document.createElement('div');
    render();
}
function render() {
    // simplified: rebuild first line with SO items
}
</script>
@endpush
@endsection