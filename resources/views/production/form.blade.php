@extends('layouts.app')
@section('title', ' - Batch Produksi')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">{{ $batch ? 'Edit' : 'Buat' }} Batch Produksi</h1>
<form method="POST" action="{{ $batch ? route('production-batches.update', $batch) : route('production-batches.store') }}" class="space-y-4">
    @csrf @if($batch) @method('PUT') @endif
    <div class="bg-white rounded-xl border border-slate-200 p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}" @selected(old('company_id', $batch?->company_id))>{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($sites as $s)<option value="{{ $s->id }}" @selected(old('site_id', $batch?->site_id))>{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Crusher</label>
            <select name="crusher_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($crushers as $c)<option value="{{ $c->id }}" @selected(old('crusher_id', $batch?->crusher_id))>{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="date" value="{{ old('date', $batch?->date?->toDateString() ?? today()->toDateString()) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Shift</label>
            <select name="shift_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($shifts as $s)<option value="{{ $s->id }}" @selected(old('shift_id', $batch?->shift_id))>{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Operator</label>
            <select name="operator_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($operators as $o)<option value="{{ $o->id }}" @selected(old('operator_id', $batch?->operator_id))>{{ $o->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Mulai</label>
            <input type="datetime-local" name="start_time" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Selesai</label>
            <input type="datetime-local" name="finish_time" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-sm mb-3">Input Material</h3>
            <div id="inputs">
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_6rem] gap-2 mb-2 line">
                    <select name="inputs[0][item_id]" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm min-w-0"><option value="">--</option>@foreach ($rawItems as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select>
                    <select name="inputs[0][warehouse_id]" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm min-w-0"><option value="">Gudang</option>@foreach ($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select>
                    <input name="inputs[0][tonnage]" type="number" step="0.0001" placeholder="Ton" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm">
                </div>
            </div>
            <button type="button" onclick="addLine('inputs', 'Ton')" class="text-xs text-amber-600">+ Tambah input</button>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-sm mb-3">Output Produk</h3>
            <div id="outputs">
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_6rem] gap-2 mb-2 line">
                    <select name="outputs[0][item_id]" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm min-w-0"><option value="">--</option>@foreach ($productItems as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select>
                    <select name="outputs[0][warehouse_id]" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm min-w-0"><option value="">Gudang</option>@foreach ($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select>
                    <input name="outputs[0][gross_tonnage]" type="number" step="0.0001" placeholder="Ton" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm">
                </div>
            </div>
            <button type="button" onclick="addLine('outputs', 'Ton')" class="text-xs text-amber-600">+ Tambah output</button>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-sm mb-3">Loss & Scrap</h3>
            <div id="losses">
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_6rem] gap-2 mb-2 line">
                    <select name="losses[0][category]" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm min-w-0">@foreach ($lossCategories as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select>
                    <input name="losses[0][tonnage]" type="number" step="0.0001" placeholder="Ton" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm">
                </div>
            </div>
            <button type="button" onclick="addLine('losses', 'Ton')" class="text-xs text-amber-600 mr-3">+ Loss</button>
            <span class="text-xs text-slate-400">NET = GROSS − LOSS − SCRAP</span>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <textarea name="notes" rows="2" placeholder="Catatan batch" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">{{ old('notes', $batch?->notes) }}</textarea>
    </div>
    <div class="form-actions-sticky flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white">
        <button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Simpan</button>
        <a href="{{ route('production-batches.index') }}" class="px-5 py-2.5 rounded-lg bg-slate-100 text-sm min-h-[44px] inline-flex items-center">Batal</a>
    </div>
</form>

@push('scripts')
<script>
let counters = {inputs: 1, outputs: 1, losses: 1};
function addLine(group, ph) {
    const el = document.getElementById(group).querySelector('.line').cloneNode(true);
    el.querySelectorAll('input,select').forEach(inp => { inp.name = inp.name.replace(/\[\d+\]/, '[' + counters[group] + ']'); if (inp.tagName === 'INPUT') inp.value = ''; });
    document.getElementById(group).appendChild(el);
    counters[group]++;
}
</script>
@endpush
@endsection