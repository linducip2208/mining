@extends('layouts.app')

@section('title', ' - Trip ' . $trip->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('dispatch.trips.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar trip</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">Trip {{ $trip->number }} <x-status-badge :status="$trip->status" /></h1>
    <p class="text-sm text-slate-500">{{ $trip->trip_date }} · {{ $trip->truck?->code }} / {{ $trip->driver?->name }} · {{ $trip->loadingPoint?->name }} → {{ $trip->dumpingPoint?->name }} · Tonase: {{ $trip->tonnage ? number_format($trip->tonnage, 2) . ' T' : '—' }}</p>
</div>

@can('dispatch.close')
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="text-sm font-bold text-slate-700 mb-3">Aksi Trip</div>
    <div class="flex flex-wrap gap-2">
        @foreach (['LOADING' => 'Mulai Loading', 'HAULING' => 'Mulai Hauling', 'DUMPED' => 'Sudah Dumping', 'COMPLETED' => 'Selesaikan'] as $phase => $label)
        <form method="POST" action="{{ route('dispatch.trips.stamp', $trip) }}">
            @csrf
            <input type="hidden" name="phase" value="{{ $phase }}">
            <button class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-semibold">{{ $label }}</button>
        </form>
        @endforeach
    </div>
    <form method="POST" action="{{ route('dispatch.trips.link-ticket', $trip) }}" class="flex flex-wrap items-end gap-2 mt-3">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tautkan Tiket Timbangan</label>
            <select name="weighbridge_ticket_id" required class="mt-1 px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none min-w-[240px]">
                <option value="">-- Pilih tiket --</option>
                @foreach ($tickets ?? [] as $tk)
                    <option value="{{ $tk->id }}">{{ $tk->number }} — {{ number_format($tk->net_weight, 0) }} kg</option>
                @endforeach
            </select>
        </div>
        <button class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">Tautkan</button>
    </form>
    <form method="POST" action="{{ route('dispatch.trips.cancel', $trip) }}" class="flex flex-wrap items-end gap-2 mt-3" onsubmit="return confirm('Batalkan trip ini?')">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Alasan Pembatalan</label>
            <input type="text" name="reason" maxlength="500" class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none min-w-[240px]">
        </div>
        <button class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold">Batalkan Trip</button>
    </form>
</div>
@endcan

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-sm font-bold text-slate-700 mb-3">Detail Waktu</div>
    <div class="grid md:grid-cols-4 gap-3 text-sm">
        <div><div class="text-[11px] uppercase text-slate-500">Berangkat</div><div class="font-semibold">{{ $trip->start_time ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Loading</div><div class="font-semibold">{{ $trip->loading_time ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Hauling</div><div class="font-semibold">{{ $trip->hauling_time ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Dumping</div><div class="font-semibold">{{ $trip->dumping_time ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Loader</div><div class="font-semibold">{{ $trip->loader?->code ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Rute</div><div class="font-semibold">{{ $trip->route?->name ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Tiket WB</div><div class="font-semibold">{{ $trip->ticket?->number ?? '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Catatan</div><div class="font-semibold">{{ $trip->notes ?? '—' }}</div></div>
    </div>
</div>
@endsection
