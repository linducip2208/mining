@extends('layouts.app')
@section('title', ' - WO Baru')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Work Order</h1>
<form method="POST" action="{{ route('work-orders.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Peralatan</label>
            <select name="equipment_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($equipment as $e)<option value="{{ $e->id }}">{{ $e->code }} - {{ $e->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="PREVENTIVE">Preventive</option><option value="CORRECTIVE">Corrective</option><option value="BREAKDOWN">Breakdown</option></select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Prioritas</label>
            <select name="priority" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="LOW">Rendah</option><option value="NORMAL" selected>Normal</option><option value="HIGH">Tinggi</option><option value="URGENT">Mendesak</option></select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Estimasi Biaya</label>
            <input type="number" step="0.01" name="estimated_cost" value="0" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Deskripsi Masalah</label>
            <input name="description" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div class="grid md:grid-cols-2 gap-4 mt-4">
        <div>
            <h3 class="font-semibold text-sm mb-2">Sparepart</h3>
            <div id="parts">
                <div class="flex gap-2 mb-2 line">
                    <select name="parts[0][item_id]" class="flex-1 px-2 py-1.5 rounded border border-slate-200 text-sm"><option value="">-- Sparepart --</option>@foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select>
                    <select name="parts[0][warehouse_id]" class="flex-1 px-2 py-1.5 rounded border border-slate-200 text-sm"><option value="">Gudang</option>@foreach ($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select>
                    <input name="parts[0][qty]" type="number" step="0.01" placeholder="Qty" class="w-20 px-2 py-1.5 rounded border border-slate-200 text-sm">
                </div>
            </div>
            <button type="button" onclick="addLine('parts')" class="text-xs text-amber-600">+ Sparepart</button>
        </div>
        <div>
            <h3 class="font-semibold text-sm mb-2">Teknisi</h3>
            <div id="techs">
                <div class="flex gap-2 mb-2 tline">
                    <select name="technicians[0][employee_id]" class="flex-1 px-2 py-1.5 rounded border border-slate-200 text-sm"><option value="">-- Teknisi --</option>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
                    <input name="technicians[0][hours]" type="number" step="0.5" placeholder="Jam" class="w-20 px-2 py-1.5 rounded border border-slate-200 text-sm">
                </div>
            </div>
            <button type="button" onclick="addLine('techs')" class="text-xs text-amber-600">+ Teknisi</button>
        </div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('work-orders.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@push('scripts')
<script>
let cp = 1, ct = 1;
function addLine(group) {
    if (group === 'parts') {
        const el = document.querySelector('#parts .line').cloneNode(true);
        el.querySelectorAll('select,input').forEach(i => { i.name = i.name.replace(/\[\d+\]/, '[' + cp + ']'); if (i.tagName === 'INPUT') i.value = ''; });
        document.getElementById('parts').appendChild(el); cp++;
    } else {
        const el = document.querySelector('#techs .tline').cloneNode(true);
        el.querySelectorAll('select,input').forEach(i => { i.name = i.name.replace(/\[\d+\]/, '[' + ct + ']'); if (i.tagName === 'INPUT') i.value = ''; });
        document.getElementById('techs').appendChild(el); ct++;
    }
}
</script>
@endpush
@endsection