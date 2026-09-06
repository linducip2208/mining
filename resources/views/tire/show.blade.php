@extends('layouts.app')

@section('title', ' - Ban ' . $tire->serial_no)

@section('content')
<div class="mb-4">
    <a href="{{ route('tires.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar ban</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">Ban {{ $tire->serial_no }} <x-status-badge :status="$tire->status" /></h1>
    <p class="text-sm text-slate-500">{{ $tire->brand }} {{ $tire->size }} {{ $tire->pattern }} · Terpasang: {{ $tire->equipment?->code ?? '—' }} ({{ $tire->position ?? '—' }})</p>
</div>

<div class="grid md:grid-cols-2 gap-4 mb-4">
    @can('tire.update')
    <form method="POST" action="{{ route('tires.install', $tire) }}" class="bg-white rounded-xl border border-slate-200 p-4">
        @csrf
        <div class="text-sm font-bold text-slate-700 mb-3">Pasang ke Unit</div>
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="text-xs font-semibold text-slate-600 uppercase">Unit</label>
                <select name="equipment_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                    <option value="">-- Pilih --</option>
                    @foreach ($units ?? [] as $u)
                        <option value="{{ $u->id }}">{{ $u->code }} — {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Posisi</label>
                <input type="text" name="position" required maxlength="30" placeholder="FL / FR / RL..." class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
                <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">HM</label>
                <input type="number" step="0.01" min="0" name="hm" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div class="flex items-end"><button class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">Pasang</button></div>
        </div>
    </form>

    <form method="POST" action="{{ route('tires.remove', $tire) }}" class="bg-white rounded-xl border border-slate-200 p-4">
        @csrf
        <div class="text-sm font-bold text-slate-700 mb-3">Lepas dari Unit</div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
                <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">HM</label>
                <input type="number" step="0.01" min="0" name="hm" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div class="col-span-2">
                <label class="text-xs font-semibold text-slate-600 uppercase">Alasan</label>
                <input type="text" name="reason" required maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div class="col-span-2 flex items-center gap-4">
                <label class="text-xs text-slate-600 flex items-center gap-1.5"><input type="checkbox" name="to_scrap" value="1" class="rounded"> Langsung scrap</label>
                <button class="px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">Lepas</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('tires.rotate', $tire) }}" class="bg-white rounded-xl border border-slate-200 p-4">
        @csrf
        <div class="text-sm font-bold text-slate-700 mb-3">Rotasi Posisi</div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Posisi Baru</label>
                <input type="text" name="position" required maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
                <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
        </div>
        <button class="mt-3 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Rotasi</button>
    </form>

    <form method="POST" action="{{ route('tires.repair', $tire) }}" class="bg-white rounded-xl border border-slate-200 p-4">
        @csrf
        <div class="text-sm font-bold text-slate-700 mb-3">Catat Repair</div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
                <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Biaya (Rp)</label>
                <input type="number" step="0.01" min="0" name="cost" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div class="col-span-2">
                <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
                <input type="text" name="notes" maxlength="1000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
        </div>
        <button class="mt-3 px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold">Simpan Repair</button>
    </form>
    @endcan
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-sm font-bold text-slate-700 mb-3">Riwayat Pergerakan</div>
    <x-table>
        <x-slot:head>
            <th class="px-4 py-2.5">Tanggal</th>
            <th class="px-4 py-2.5">Jenis</th>
            <th class="px-4 py-2.5">Unit</th>
            <th class="px-4 py-2.5">Posisi</th>
            <th class="px-4 py-2.5 text-right">HM</th>
            <th class="px-4 py-2.5 text-right">Biaya</th>
            <th class="px-4 py-2.5">Keterangan</th>
        </x-slot:head>
        @forelse ($tire->movements ?? [] as $m)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $m->date ?? $m->created_at }}</td>
            <td class="px-4 py-2.5">{{ $m->type }}</td>
            <td class="px-4 py-2.5 font-mono">{{ $m->equipment?->code }}</td>
            <td class="px-4 py-2.5">{{ $m->position }}</td>
            <td class="px-4 py-2.5 text-right">{{ $m->hm }}</td>
            <td class="px-4 py-2.5 text-right">{{ $m->cost ? 'Rp ' . number_format($m->cost, 0) : '—' }}</td>
            <td class="px-4 py-2.5 text-xs text-slate-600">{{ $m->notes }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada riwayat</td></tr>
        @endforelse
    </x-table>
</div>
@endsection
