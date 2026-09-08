@extends('layouts.app')
@section('title', ' - BFJ Master Mapping')
@section('content')
<div class="mb-4"><a href="{{ route('bfj.show', $batch) }}" class="text-xs text-slate-500">← Batch #{{ $batch->id }}</a>
<h1 class="text-xl font-bold text-slate-800">Master Mapping Queue</h1>
<p class="text-sm text-slate-500">Bulk-resolve legacy values. Fuzzy suggestions never auto-link — explicit MATCH / CREATE / IGNORE only.</p></div>
<div class="bg-white rounded-xl border border-slate-200 p-4">
<table class="w-full text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th>Entity</th><th>Legacy</th><th>Normalized</th><th>Conf</th><th>Status</th><th>Resolve</th></tr></thead>
<tbody>@foreach($matches as $m)<tr class="border-t border-slate-100">
<td class="py-1">{{ $m->entity_type }}</td><td class="py-1 font-medium">{{ $m->legacy_value }}</td><td class="py-1 font-mono text-xs">{{ $m->normalized_value }}</td>
<td class="py-1">{{ $m->confidence }}%</td><td class="py-1">{{ $m->status }}</td>
<td class="py-1"><form method="POST" action="{{ route('bfj.resolve', $batch) }}" class="flex gap-1 items-center">@csrf
<input type="hidden" name="match_id" value="{{ $m->id }}">
<input type="text" name="target_type" placeholder="App\Models\Customer" class="text-xs border rounded px-1 py-1 w-40" value="{{ $m->target_type }}">
<input type="number" name="target_id" placeholder="id" class="text-xs border rounded px-1 py-1 w-16" value="{{ $m->target_id }}">
<select name="decision" class="text-xs border rounded px-1 py-1"><option value="match">MATCH</option><option value="create">CREATE</option><option value="ignore">IGNORE</option></select>
<button class="text-xs text-indigo-600">save</button></form></td></tr>@endforeach</tbody></table>
{{ $matches->links() }}
</div>
@endsection
