@extends('layouts.app')
@section('title', ' - Timbang Pertama')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Timbang Pertama (Ticket Baru)</h1>
<form method="POST" action="{{ route('weighbridge.first') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Timbangan</label>
            <select name="weighbridge_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach ($weighbridges as $wb)<option value="{{ $wb->id }}" @selected(old('weighbridge_id'))>{{ $wb->code }} - {{ $wb->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Arah</label>
            <select name="direction" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="OUT">Keluar (Muat Barang)</option>
                <option value="IN">Masuk (Terima Barang)</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach ($sites as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">No Polisi</label>
            <input type="text" name="vehicle_plate" required placeholder="B 1234 XYZ" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm uppercase">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Pengemudi</label>
            <input type="text" name="driver_name" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Berat Terbaca (kg)</label>
            <input type="number" step="0.01" name="weight" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-mono text-lg">
        </div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Timbang Pertama</button>
        <a href="{{ route('weighbridge-tickets.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection