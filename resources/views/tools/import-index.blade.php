@extends('layouts.app')
@section('title', ' - Import Data')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Import Data Legacy</h1>
        <p class="text-sm text-slate-500">Upload → Sheet → Map (auto) → Validate → Preview → Import. Native .xlsx / .xls / .csv — macro (.xlsm) ditolak.</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="font-semibold text-sm mb-3">Upload Baru</h2>
    <form method="POST" action="{{ route('imports.upload') }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-3 items-end">
        @csrf
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" required class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full">@foreach($types as $k => $t)<option value="{{ $k }}">{{ $t['label'] }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">File (.xlsx / .xls / .csv)</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full">
                <option value="">— default pertama —</option>
                @foreach(\App\Models\Company::orderBy('name')->get() as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Gudang (untuk stok awal)</label>
            <select name="warehouse_id" class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full">
                <option value="">— per baris dari kolom WAREHOUSE —</option>
                @foreach(\App\Models\Warehouse::orderBy('name')->get() as $w)
                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Mode Import</label>
            <select name="mode" class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full">
                @foreach($modes ?? [] as $typeKey => $modeList)
                    <optgroup label="{{ $types[$typeKey]['label'] ?? $typeKey }}">
                        @foreach($modeList as $mode)
                            <option value="{{ $mode }}">{{ $mode }}{{ ($modes[$typeKey][0] ?? '') === $mode ? ' (default)' : '' }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select></div>
        <div class="md:col-span-3">
            <button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Upload</button>
            <p class="mt-2 text-xs text-slate-500">File identik yang pernah diimport akan meminta konfirmasi force. Duplikat baris dideteksi otomatis via fingerprint.</p>
        </div>
    </form>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Batch</th><th class="px-4 py-2.5">Tipe</th><th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Valid/Gagal</th><th class="px-4 py-2.5 text-right">Imported</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($batches as $b)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-mono text-xs">#{{ $b->id }} {{ basename($b->file_name) }}@if($b->sheet)<span class="text-slate-400"> · {{ $b->sheet }}</span>@endif</td>
            <td class="px-4 py-2.5 text-xs">{{ $types[$b->type]['label'] ?? $b->type }}@if($b->mode)<span class="text-slate-400"> · {{ $b->mode }}</span>@endif</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$b->status" /></td>
            <td class="px-4 py-2.5 text-right text-xs">{{ $b->valid_rows }}/{{ $b->failed_rows }}</td>
            <td class="px-4 py-2.5 text-right text-xs">{{ $b->imported_rows }} (skip {{ $b->skipped_rows }})</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                <a href="{{ route('imports.map', $b) }}" class="text-amber-600 hover:underline text-xs">Map</a>
                <a href="{{ route('imports.errors', $b) }}" class="text-slate-500 hover:underline text-xs ml-2">Errors CSV</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada batch</td></tr>
        @endforelse
    </tbody>
</x-table>
@endsection
