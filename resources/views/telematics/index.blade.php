@extends('layouts.app')

@section('title', ' - Telematics')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Telematics</h1>
    <p class="text-sm text-slate-500">GPS & sensor unit: simulator bawaan atau REST API provider</p>
</div>

@can('telematics.create')
<form method="POST" action="{{ route('telematics.providers.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="text-sm font-bold text-slate-700 mb-2">Tambah Provider</div>
    <div class="grid md:grid-cols-5 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input type="text" name="code" required maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input type="text" name="name" required maxlength="150" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Driver</label>
            <select name="driver" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                @foreach ($drivers ?? [] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Endpoint</label>
            <input type="url" name="endpoint" placeholder="https://..." class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div class="flex items-end gap-2">
            <label class="text-xs text-slate-600 flex items-center gap-1.5 pb-2"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Aktif</label>
            <button class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">Simpan</button>
        </div>
    </div>
</form>
@endcan

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="text-sm font-bold text-slate-700 mb-2">Provider</div>
    <div class="flex flex-wrap gap-2">
        @forelse ($providers ?? [] as $p)
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
            <span class="font-mono font-bold">{{ $p->code }}</span>
            <span class="text-slate-500">{{ $p->driver }}</span>
            <x-status-badge :status="$p->is_active ? 'ACTIVE' : 'INACTIVE'" />
            @can('telematics.update')
            <form method="POST" action="{{ route('telematics.providers.sync', $p) }}" class="inline">@csrf<button class="text-indigo-600 hover:underline">Sync</button></form>
            @endcan
        </div>
        @empty
        <span class="text-sm text-slate-400">Belum ada provider.</span>
        @endforelse
    </div>
</div>

<x-filter-bar :route="route('telematics.index')">
    <x-filter-input name="event_type" label="Tipe" type="select" :options="['LOCATION' => 'Lokasi', 'SPEED' => 'Kecepatan', 'IGNITION' => 'Ignition', 'ENGINE_HOUR' => 'Engine Hour', 'ODOMETER' => 'Odometer', 'FUEL_LEVEL' => 'Level BBM', 'GEOFENCE' => 'Geofence', 'TRIP' => 'Trip', 'IDLE' => 'Idle']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Waktu</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Provider</th>
        <th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5 text-right">Nilai</th>
        <th class="px-4 py-2.5">Koordinat</th>
    </x-slot:head>
    @forelse ($events as $e)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5 text-xs">{{ $e->event_time }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $e->equipment?->code ?? '—' }}</td>
        <td class="px-4 py-2.5">{{ $e->provider?->code }}</td>
        <td class="px-4 py-2.5">{{ $e->event_type }}</td>
        <td class="px-4 py-2.5 text-right">{{ $e->value }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $e->latitude }}, {{ $e->longitude }}</td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada event — jalankan Sync pada provider</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $events->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
