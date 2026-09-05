@extends('layouts.app')
@section('title', ' - Aktivitas Tambang')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">{{ $activity ? 'Edit' : 'Catat' }} Aktivitas Tambang</h1>
<form method="POST" action="{{ $activity ? route('mining-activities.update', $activity) : route('mining-activities.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf @if($activity) @method('PUT') @endif
    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach ($companies as $id => $n)<option value="{{ $id }}" @selected(old('company_id', $activity?->company_id))>{{ $n }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach ($sites as $s)<option value="{{ $s->id }}" @selected(old('site_id', $activity?->site_id))>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Pit</label>
            <select name="pit_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">--</option>
                @foreach ($pits as $p)<option value="{{ $p->id }}" @selected(old('pit_id', $activity?->pit_id))>{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="date" value="{{ old('date', $activity?->date?->toDateString() ?? today()->toDateString()) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Shift</label>
            <select name="shift_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">--</option>
                @foreach ($shifts as $s)<option value="{{ $s->id }}" @selected(old('shift_id', $activity?->shift_id))>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jenis</label>
            <select name="activity_type" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach (['MINING' => 'Penambangan', 'HAULING' => 'Hauling', 'CLEANING' => 'Pembersihan', 'DRILLING' => 'Drilling', 'OTHER' => 'Lainnya'] as $v => $t)
                    <option value="{{ $v }}" @selected(old('activity_type', $activity?->activity_type ?? 'MINING'))>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Peralatan</label>
            <select name="equipment_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">--</option>
                @foreach ($equipments as $e)<option value="{{ $e->id }}" @selected(old('equipment_id', $activity?->equipment_id))>{{ $e->code }} - {{ $e->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Operator</label>
            <select name="operator_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">--</option>
                @foreach ($operators as $o)<option value="{{ $o->id }}" @selected(old('operator_id', $activity?->operator_id))>{{ $o->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Material</label>
            <select name="item_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">--</option>
                @foreach ($items as $i)<option value="{{ $i->id }}" @selected(old('item_id', $activity?->item_id))>{{ $i->code }} - {{ $i->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Stockpile Tujuan</label>
            <select name="target_warehouse_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="">--</option>
                @foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(old('target_warehouse_id', $activity?->target_warehouse_id))>{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jumlah (Trip/Rit)</label>
            <input type="number" step="0.0001" name="quantity" value="{{ old('quantity', $activity?->quantity) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tonase (Ton)</label>
            <input type="number" step="0.0001" name="tonnage" value="{{ old('tonnage', $activity?->tonnage) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jam Kerja</label>
            <input type="number" step="0.01" name="working_hours" value="{{ old('working_hours', $activity?->working_hours) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
            <textarea name="notes" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">{{ old('notes', $activity?->notes) }}</textarea>
        </div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('mining-activities.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection