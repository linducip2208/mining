@extends('layouts.app')

@section('title', ' - ' . $report->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('hse.reports.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $report->number }} <x-status-badge :status="$report->status" /></h1>
    <p class="text-sm text-slate-500">{{ $report->kind }} · {{ $report->occurred_at }} · {{ $report->location }} · Severity {{ $report->severity }}</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4 text-sm space-y-2">
    <div><span class="text-[11px] uppercase text-slate-500">Deskripsi</span><div>{{ $report->description }}</div></div>
    @if ($report->cause)<div><span class="text-[11px] uppercase text-slate-500">Penyebab</span><div>{{ $report->cause }}</div></div>@endif
    @if ($report->root_cause)<div><span class="text-[11px] uppercase text-slate-500">Root Cause (investigasi)</span><div>{{ $report->root_cause }}</div></div>@endif
    @if ($report->immediate_action)<div><span class="text-[11px] uppercase text-slate-500">Tindakan Segera</span><div>{{ $report->immediate_action }}</div></div>@endif
    @can('hse.update')
    @if (!in_array($report->status, ['CLOSED', 'CANCELLED']))
    <form method="POST" action="{{ route('hse.reports.investigate', $report) }}" class="grid md:grid-cols-2 gap-2 pt-2 border-t border-slate-100">
        @csrf
        <input type="text" name="cause" value="{{ $report->cause }}" maxlength="5000" placeholder="Penyebab (opsional)..." class="px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        <div class="flex gap-2">
            <input type="text" name="root_cause" required maxlength="5000" placeholder="Root cause wajib..." class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            <button class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">Investigasi</button>
        </div>
    </form>
    @endif
    @endcan
    @can('hse.update')
    @if (!in_array($report->status, ['CLOSED', 'CANCELLED']))
    <form method="POST" action="{{ route('hse.reports.close', $report) }}" onsubmit="return confirm('Ajukan penutupan kasus ini ke approval center?')">
        @csrf<button class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-semibold">Ajukan Penutupan</button>
    </form>
    @endif
    @endcan
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-sm font-bold text-slate-700 mb-3">Corrective Action</div>
    <x-table>
        <x-slot:head>
            <th class="px-4 py-2.5">Tindakan</th>
            <th class="px-4 py-2.5">Penanggung Jawab</th>
            <th class="px-4 py-2.5">Jatuh Tempo</th>
            <th class="px-4 py-2.5">Status</th>
            <th class="px-4 py-2.5 text-right">Aksi</th>
        </x-slot:head>
        @forelse ($report->correctiveActions ?? [] as $a)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 text-xs">{{ $a->action }}</td>
            <td class="px-4 py-2.5">{{ $a->responsible?->name ?? '—' }}</td>
            <td class="px-4 py-2.5">{{ $a->due_date ?? '—' }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$a->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @can('hse.update')
                @if ($a->status === 'OPEN')
                <form method="POST" action="{{ route('hse.actions.close', $a) }}" enctype="multipart/form-data" class="inline-flex gap-1 items-center">
                    @csrf
                    <input type="text" name="evidence" required maxlength="5000" placeholder="Evidence..." class="px-2 py-1 rounded border border-slate-200 text-xs w-36">
                    <input type="file" name="evidence_file" class="text-[11px] w-32">
                    <button class="text-green-600 hover:underline text-xs">Tutup</button>
                </form>
                @endif
                @endcan
                @can('hse.close')
                @if ($a->status === 'DONE')
                <form method="POST" action="{{ route('hse.actions.verify', $a) }}" class="inline ml-2">
                    @csrf<button class="text-indigo-600 hover:underline text-xs">Verifikasi</button>
                </form>
                @endif
                @endcan
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada corrective action</td></tr>
        @endforelse
    </x-table>

    @can('hse.create')
    <form method="POST" action="{{ route('hse.reports.actions.store', $report) }}" class="mt-3 grid md:grid-cols-4 gap-2">
        @csrf
        <input type="text" name="action" required maxlength="2000" placeholder="Tindakan perbaikan..." class="md:col-span-2 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        <select name="responsible_id" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
            <option value="">Penanggung jawab...</option>
            @foreach ($employees ?? [] as $e)
                <option value="{{ $e->id }}">{{ $e->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input type="date" name="due_date" class="px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            <button class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">Tambah</button>
        </div>
    </form>
    @endcan
</div>
@endsection
