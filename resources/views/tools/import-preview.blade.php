@extends('layouts.app')
@section('title', ' - Preview Import')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Preview — Batch #{{ $batch->id }}</h1>

<div class="grid grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
    @foreach([['Total', $result['total']], ['Valid', $result['valid']], ['Warning', $result['warnings']], ['Gagal', $result['failed']], ['Akan Import', $result['valid'] + $result['warnings']], ['Duplikat skip', 'saat eksekusi']] as [$l, $v])
    <div class="dashboard-card p-3"><div class="text-xs text-slate-500">{{ $l }}</div><div class="text-xl font-bold">{{ $v }}</div></div>
    @endforeach
</div>

@if(count($result['errors']) > 0)
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
    <div class="flex items-center justify-between mb-2">
        <h2 class="font-semibold text-sm text-red-800">Error ({{ count($result['errors']) }})</h2>
        <a href="{{ route('imports.errors', $batch) }}" class="text-xs text-red-700 underline">Download error CSV</a>
    </div>
    @foreach(array_slice($result['errors'], 0, 15) as $e)
    <div class="text-xs text-red-700">Baris {{ $e['row'] }}: {{ $e['error'] }}</div>
    @endforeach
</div>
@endif

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Baris</th>
        @foreach(array_keys($batch->column_map ?? []) as $h)<th class="px-4 py-2.5">{{ $batch->column_map[$h] ?: $h }}</th>@endforeach
        <th class="px-4 py-2.5">Error</th>
    </x-slot:head>
    <tbody>
        @foreach ($result['rows'] as $r)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 text-xs">{{ $r['_row'] }}</td>
            @foreach(array_keys($batch->column_map ?? []) as $h)<td class="px-4 py-2.5 text-xs">{{ $r[$batch->column_map[$h]] ?? '' }}</td>@endforeach
            <td class="px-4 py-2.5 text-xs text-red-600">{{ implode('; ', $r['_errors']) }}</td>
        </tr>
        @endforeach
    </tbody>
</x-table>

@if($result['failed'] === 0 && ($result['valid'] + $result['warnings']) > 0)
<form method="POST" action="{{ route('imports.execute', $batch) }}" class="mt-4" onsubmit="return confirm('Eksekusi import? Duplikat dilewati otomatis.')">
    @csrf
    <button class="px-5 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold min-h-[44px]">Eksekusi Import ({{ $result['valid'] + $result['warnings'] }} baris)</button>
</form>
@else
<p class="text-sm text-slate-500 mt-4">Perbaiki error di file sumber, upload ulang sebagai batch baru.</p>
@endif
@endsection
