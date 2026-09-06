@extends('layouts.app')

@section('title', ' - Assignment Operator')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Assignment Operator</h1>
    <p class="text-sm text-slate-500">Penugasan unit-operator per site & shift harian</p>
</div>

@can('fleet.create')
<form method="POST" action="{{ route('fleet.assignments.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
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
            <label class="text-xs font-semibold text-slate-600 uppercase">Operator</label>
            <select name="employee_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($operators ?? [] as $o)
                    <option value="{{ $o->id }}">{{ $o->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Pit</label>
            <select name="pit_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach (\App\Models\Pit::orderBy('name')->get() as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
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
            <label class="text-xs font-semibold text-slate-600 uppercase">Jam Kerja</label>
            <input type="number" step="0.01" min="0" name="working_hours" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
            <input type="text" name="notes" maxlength="1000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Assignment</button>
</form>
@endcan

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Operator</th>
        <th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Shift</th>
        <th class="px-4 py-2.5 text-right">Jam Kerja</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items ?? [] as $a)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $a->date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $a->equipment?->code }}</td>
        <td class="px-4 py-2.5">{{ $a->employee?->name }}</td>
        <td class="px-4 py-2.5">{{ $a->site?->name }}</td>
        <td class="px-4 py-2.5">{{ $a->shift?->name }}</td>
        <td class="px-4 py-2.5 text-right">{{ $a->working_hours }}</td>
        <td class="px-4 py-2.5">{{ $a->status }}</td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
