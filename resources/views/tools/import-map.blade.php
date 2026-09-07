@extends('layouts.app')
@section('title', ' - Mapping Kolom')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-1">Mapping Kolom — {{ $def['label'] }}</h1>
<p class="text-sm text-slate-500 mb-4">Batch #{{ $batch->id }} · petakan header CSV ke field sistem.</p>

<form method="POST" action="{{ route('imports.validate', $batch) }}" class="bg-white rounded-xl border border-slate-200 p-5">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($data['headers'] as $h)
        <div class="flex items-center gap-2">
            <span class="font-mono text-xs flex-1 truncate" title="{{ $h }}">{{ $h }}</span>
            <span class="text-slate-400">→</span>
            <select name="map[{{ $h }}]" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] flex-1">
                <option value="">(abaikan)</option>
                @foreach($def['fields'] as $field => $label)
                <option value="{{ $field }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endforeach
    </div>
    <div class="mt-4"><button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Validasi & Preview</button></div>
</form>
@endsection
