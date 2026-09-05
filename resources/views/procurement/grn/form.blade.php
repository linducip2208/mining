@extends('layouts.app')
@section('title', ' - GRN Baru')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Penerimaan Barang</h1>
<form method="POST" action="{{ route('goods-receipts.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4">
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Purchase Order</label>
            <select name="purchase_order_id" id="poSelect" required onchange="loadPoLines(this.value)" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">-- Pilih PO --</option>
                @foreach ($pos as $po)<option value="{{ $po->id }}" data-lines='@json($po->items->map(fn($i) => ["item_id" => $i->item_id, "item" => $i->item?->name]))'>{{ $po->number }} - {{ $po->supplier?->name }}</option>@endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Gudang</label>
            <select name="warehouse_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($warehouses as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="receipt_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">QC</label>
            <select name="qc_status" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="PASSED">Lolos</option><option value="PENDING">Pending</option><option value="FAILED">Gagal</option></select></div>
    </div>
    <div id="lines" class="mt-4 space-y-2"></div>
    <p class="text-xs text-slate-400 mt-2">Pilih PO untuk memuat item. Qty diterima = datang, Qty diterima baik = masuk stok.</p>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('goods-receipts.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@push('scripts')
<script>
let c = 0;
function loadPoLines(poId) {
    const sel = document.getElementById('poSelect');
    const opt = sel.selectedOptions[0];
    const lines = JSON.parse(opt.dataset.lines || '[]');
    const box = document.getElementById('lines');
    box.innerHTML = '';
    c = 0;
    lines.forEach(l => {
        box.insertAdjacentHTML('beforeend', `
        <div class="flex gap-2 items-center">
            <input type="hidden" name="lines[${c}][item_id]" value="${l.item_id}">
            <span class="flex-1 text-sm">${l.item}</span>
            <input name="lines[${c}][qty_received]" type="number" step="0.0001" placeholder="Diterima" class="w-32 px-3 py-2 rounded-lg border border-slate-200 text-sm" required>
            <input name="lines[${c}][qty_accepted]" type="number" step="0.0001" placeholder="Diterima baik" class="w-32 px-3 py-2 rounded-lg border border-slate-200 text-sm" required>
        </div>`);
        c++;
    });
}
</script>
@endpush
@endsection