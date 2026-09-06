@extends('layouts.app')

@section('title', ' - Inspeksi Unit')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Inspeksi Unit</h1>
    <p class="text-sm text-slate-500">P2H / inspeksi harian — hasil FAIL otomatis BREAKDOWN</p>
</div>

@can('fleet.create')
<form method="POST" action="{{ route('fleet.inspections.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
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
            <input type="date" name="inspection_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
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
            <label class="text-xs font-semibold text-slate-600 uppercase">Hasil</label>
            <select name="result" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="PASS">PASS</option>
                <option value="CONDITIONAL">CONDITIONAL</option>
                <option value="FAIL">FAIL</option>
            </select>
        </div>
        <div class="md:col-span-4">
            <label class="text-xs font-semibold text-slate-600 uppercase">Temuan</label>
            <textarea name="findings" rows="2" maxlength="2000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none"></textarea>
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Inspeksi</button>
</form>
@endcan

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Shift</th>
        <th class="px-4 py-2.5">Inspektur</th>
        <th class="px-4 py-2.5">Hasil</th>
        <th class="px-4 py-2.5">Temuan</th>
    </x-slot:head>
    @forelse ($items ?? [] as $i)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $i->inspection_date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $i->equipment?->code }}</td>
        <td class="px-4 py-2.5">{{ $i->shift?->name }}</td>
        <td class="px-4 py-2.5">{{ $i->inspector?->name }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$i->result === 'PASS' ? 'VALIDATED' : ($i->result === 'FAIL' ? 'BREAKDOWN' : 'PENDING')" /></td>
        <td class="px-4 py-2.5 text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($i->findings, 120) }}</td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
