@extends('layouts.app')

@section('title', ' - ' . $item->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('compliance.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $item->title }} <x-status-badge :status="$item->status" /></h1>
    <p class="text-sm text-slate-500">{{ $item->number }} · No dokumen: {{ $item->document_number ?? '—' }}</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 max-w-4xl">
    <div class="grid md:grid-cols-3 gap-3 text-sm">
        <div><div class="text-[11px] uppercase text-slate-500">Tipe</div><div class="font-semibold">{{ $item->type }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Perusahaan / Site</div><div class="font-semibold">{{ $item->company?->name ?? '—' }} / {{ $item->site?->name ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Karyawan / Unit</div><div class="font-semibold">{{ $item->employee?->name ?? '—' }} / {{ $item->equipment?->code ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Terbit</div><div class="font-semibold">{{ $item->issued_date ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Berlaku</div><div class="font-semibold">{{ $item->effective_date ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Kedaluwarsa</div><div class="font-semibold {{ $item->status === 'EXPIRED' ? 'text-red-600' : '' }}">{{ $item->expiry_date ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Penanggung Jawab</div><div class="font-semibold">{{ $item->responsible?->name ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Dokumen Terkait</div><div class="font-semibold">{{ $item->document?->title ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Lampiran</div><div class="font-semibold">{{ $item->attachment ?? '—' }}</div></div>
    </div>
    @if ($item->notes)
    <div class="mt-3 text-sm"><div class="text-[11px] uppercase text-slate-500">Catatan</div><div>{{ $item->notes }}</div></div>
    @endif
    @can('compliance.update')
    @if (in_array($item->status, ['ACTIVE', 'EXPIRING_SOON', 'EXPIRED']))
    <form method="POST" action="{{ route('compliance.renew', $item) }}" class="mt-4 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-3">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perpanjang Hingga</label>
            <input type="date" name="expiry_date" required class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">No Dokumen Baru</label>
            <input type="text" name="document_number" maxlength="100" class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <button class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-semibold">Perpanjang</button>
    </form>
    @endif
    @endcan
</div>
@endsection
