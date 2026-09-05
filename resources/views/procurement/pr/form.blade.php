@extends('layouts.app')
@section('title', ' - PR Baru')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Permintaan Pembelian</h1>
<form method="POST" action="{{ route('purchase-requests.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($sites as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tgl Request</label>
            <input type="date" name="request_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Dibutuhkan Tgl</label>
            <input type="date" name="required_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div id="lines" class="mt-4 space-y-2">
        <div class="flex gap-2 line">
            <select name="lines[0][item_id]" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm"><option value="">-- Pilih Item --</option>@foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->code }} - {{ $i->name }}</option>@endforeach</select>
            <input name="lines[0][qty]" type="number" step="0.0001" placeholder="Qty" class="w-28 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <input name="lines[0][remark]" placeholder="Keterangan" class="w-48 px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
    </div>
    <button type="button" onclick="addLine()" class="mt-2 text-xs text-amber-600">+ Tambah baris</button>
    <div class="mt-4"><input name="notes" placeholder="Catatan" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('purchase-requests.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
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