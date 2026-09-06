@extends('layouts.app')

@section('title', ' - HM / Odometer')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">HM / Odometer</h1>
    <p class="text-sm text-slate-500">Pencatatan hour meter & kilometer harian per unit</p>
</div>

@can('fleet.create')
<form method="POST" action="{{ route('fleet.meters.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="grid md:grid-cols-4 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Unit</label>
            <select name="equipment_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($units ?? [] as $u)
                    <option value="{{ $u->id }}">{{ $u->code }} — {{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="log_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Shift</label>
            <select name="shift_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($shifts ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
            <select name="status" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                @foreach (['AVAILABLE', 'IN_USE', 'IDLE', 'MAINTENANCE', 'BREAKDOWN', 'STANDBY'] as $s)
                    <option value="{{ $s }}">{{ \App\Support\StatusLabel::label($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">HM Awal</label>
            <input type="number" step="0.01" min="0" name="hm_start" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">HM Akhir</label>
            <input type="number" step="0.01" min="0" name="hm_end" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">KM Awal</label>
            <input type="number" step="0.01" min="0" name="km_start" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">KM Akhir</label>
            <input type="number" step="0.01" min="0" name="km_end" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jam Operasi</label>
            <input type="number" step="0.01" min="0" name="operating_hours" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jam Idle</label>
            <input type="number" step="0.01" min="0" name="idle_hours" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Operator</label>
            <select name="operator_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($operators ?? [] as $o)
                    <option value="{{ $o->id }}">{{ $o->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
            <input type="text" name="notes" maxlength="1000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Catat HM</button>
</form>
@endcan

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Shift</th>
        <th class="px-4 py-2.5">Operator</th>
        <th class="px-4 py-2.5 text-right">HM Awal → Akhir</th>
        <th class="px-4 py-2.5 text-right">Operasi (H)</th>
        <th class="px-4 py-2.5 text-right">Idle (H)</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($logs ?? [] as $l)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $l->log_date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $l->equipment?->code }}</td>
        <td class="px-4 py-2.5">{{ $l->shift?->name }}</td>
        <td class="px-4 py-2.5">{{ $l->operator?->name }}</td>
        <td class="px-4 py-2.5 text-right">{{ $l->hm_start }} → {{ $l->hm_end }}</td>
        <td class="px-4 py-2.5 text-right">{{ $l->operating_hours }}</td>
        <td class="px-4 py-2.5 text-right">{{ $l->idle_hours }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$l->status" /></td>
    </tr>
    @empty
    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $logs->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
