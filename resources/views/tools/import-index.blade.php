@extends('layouts.app')
@section('title', ' - Import Data')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Import Data Legacy</h1>
        <p class="text-sm text-slate-500">Upload → Map → Validate → Preview → Import. Tidak langsung write.</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="font-semibold text-sm mb-3">Upload Baru</h2>
    <form method="POST" action="{{ route('imports.upload') }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
        @csrf
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" required class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">@foreach($types as $k => $t)<option value="{{ $k }}">{{ $t['label'] }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">File CSV</label>
            <input type="file" name="file" accept=".csv,.txt" required class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"></div>
        <button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Upload</button>
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
            <td class="px-4 py-2.5 font-mono text-xs">#{{ $b->id }} {{ basename($b->file_name) }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $types[$b->type]['label'] ?? $b->type }}</td>
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
