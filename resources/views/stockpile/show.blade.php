@extends('layouts.app')

@section('title', ' - ' . $pile->code)

@section('content')
<div class="mb-4">
    <a href="{{ route('stockpiles.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $pile->code }} — {{ $pile->name }}</h1>
    <p class="text-sm text-slate-500">{{ $pile->site?->name }} · {{ $pile->item?->name }} · Saldo sistem: <span class="font-bold text-slate-800">{{ number_format($balance ?? 0, 2) }} T</span></p>
</div>

@can('stockpile.reconcile')
<form method="POST" action="{{ route('stockpiles.survey', $pile) }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="text-sm font-bold text-slate-700 mb-3">Input Hasil Survei</div>
    <div class="grid md:grid-cols-4 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal Survei</label>
            <input type="date" name="survey_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Saldo Survei (T)</label>
            <input type="number" step="0.01" min="0" name="survey_balance" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Surveyor</label>
            <input type="text" name="surveyor" maxlength="150" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div class="flex items-end"><button class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Survei</button></div>
    </div>
</form>
@endcan

<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Riwayat Survei & Rekonsiliasi</div>
        <x-table>
            <x-slot:head>
                <th class="px-4 py-2.5">Tanggal</th>
                <th class="px-4 py-2.5 text-right">Survei</th>
                <th class="px-4 py-2.5 text-right">Variansi %</th>
                <th class="px-4 py-2.5">Status</th>
                <th class="px-4 py-2.5 text-right">Aksi</th>
            </x-slot:head>
            @forelse ($surveys ?? [] as $s)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5">{{ $s->survey_date }}</td>
                <td class="px-4 py-2.5 text-right">{{ number_format($s->survey_balance, 2) }}</td>
                <td class="px-4 py-2.5 text-right">{{ $s->variance_pct }}%</td>
                <td class="px-4 py-2.5"><x-status-badge :status="$s->status" /></td>
                <td class="px-4 py-2.5 text-right">
                    @can('stockpile.approve')
                    @if (in_array($s->status, ['PENDING', 'INVESTIGATE']))
                    <form method="POST" action="{{ route('stockpile-surveys.approve', $s) }}" class="inline-flex gap-1" onsubmit="return confirm('Setujui rekonsiliasi ini? Sistem akan selaras dengan survei.')">
                        @csrf
                        <input type="text" name="investigation" maxlength="2000" placeholder="Hasil investigasi..." class="px-2 py-1 rounded border border-slate-200 text-xs w-32">
                        <button class="text-green-600 hover:underline text-xs">Ajukan</button>
                    </form>
                    @endif
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada survei</td></tr>
            @endforelse
        </x-table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Mutasi Terakhir</div>
        <x-table>
            <x-slot:head>
                <th class="px-4 py-2.5">Tanggal</th>
                <th class="px-4 py-2.5">Jenis</th>
                <th class="px-4 py-2.5 text-right">Masuk</th>
                <th class="px-4 py-2.5 text-right">Keluar</th>
            </x-slot:head>
            @forelse ($movements ?? [] as $m)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5">{{ $m->trx_date }}</td>
                <td class="px-4 py-2.5">{{ $m->move_type }}</td>
                <td class="px-4 py-2.5 text-right text-green-700">{{ $m->qty_in ? number_format($m->qty_in, 2) : '—' }}</td>
                <td class="px-4 py-2.5 text-right text-red-600">{{ $m->qty_out ? number_format($m->qty_out, 2) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada mutasi</td></tr>
            @endforelse
        </x-table>
    </div>
</div>
@endsection
