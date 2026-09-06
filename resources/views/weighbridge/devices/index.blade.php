@extends('layouts.app')

@section('title', ' - Perangkat Timbangan')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Perangkat Timbangan</h1>
    <p class="text-sm text-slate-500">Bridge timbangan: manual, serial, TCP/IP, atau REST push dari device</p>
</div>

@can('weighbridge.create')
<form method="POST" action="{{ route('weighbridge.devices.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="text-sm font-bold text-slate-700 mb-2">Tambah Device</div>
    <div class="grid md:grid-cols-5 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Timbangan</label>
            <select name="weighbridge_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($weighbridges ?? [] as $w)
                    <option value="{{ $w->id }}">{{ $w->name ?? $w->code }}</option>
                @endforeach
            </select>
        </div>
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
            <input type="text" name="endpoint" maxlength="255" placeholder="COM3 / 192.168.1.10:5000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <div class="flex items-center gap-3 mt-3">
        <label class="text-xs text-slate-600 flex items-center gap-1.5"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Aktif</label>
        <button class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">Simpan</button>
    </div>
</form>
@endcan

<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Device Terdaftar</div>
        <x-table>
            <x-slot:head>
                <th class="px-4 py-2.5">Kode</th>
                <th class="px-4 py-2.5">Driver</th>
                <th class="px-4 py-2.5">Timbangan</th>
                <th class="px-4 py-2.5 text-right">Aksi</th>
            </x-slot:head>
            @forelse ($devices ?? [] as $d)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5"><span class="font-mono font-semibold">{{ $d->code }}</span><div class="text-[11px] text-slate-500">{{ $d->name }}</div></td>
                <td class="px-4 py-2.5">{{ $d->driver }}</td>
                <td class="px-4 py-2.5">{{ $d->weighbridge?->name ?? $d->weighbridge?->code }}</td>
                <td class="px-4 py-2.5 text-right"><a href="{{ route('weighbridge.devices.live', $d) }}" class="text-indigo-600 hover:underline text-xs">Live JSON</a></td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada device</td></tr>
            @endforelse
        </x-table>
        <div class="mt-3 text-[11px] text-slate-500 bg-slate-50 rounded-lg p-3">
            Endpoint device push: <code class="font-mono">POST /api/weighbridge/reading</code> dengan Bearer token device (diterbitkan saat driver REST).
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Pembacaan Terakhir</div>
        <x-table>
            <x-slot:head>
                <th class="px-4 py-2.5">Waktu</th>
                <th class="px-4 py-2.5">Device</th>
                <th class="px-4 py-2.5 text-right">Berat Stabil</th>
            </x-slot:head>
            @forelse ($readings ?? [] as $r)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5 text-xs">{{ $r->read_at }}</td>
                <td class="px-4 py-2.5 font-mono">{{ $r->device?->code }}</td>
                <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($r->stable_weight, 0) }} kg</td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada pembacaan</td></tr>
            @endforelse
        </x-table>
    </div>
</div>
@endsection
