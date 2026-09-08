@extends('layouts.app')
@section('title', ' - BFJ Acceptance')
@section('content')
<div class="mb-4"><a href="{{ route('bfj.index') }}" class="text-xs text-slate-500">← Migration Dashboard</a>
<h1 class="text-xl font-bold text-slate-800">BFJ Acceptance Report</h1>
<p class="text-sm text-slate-500">Batches: {{ implode(', ', $ids) }}</p></div>
<div class="grid gap-4 md:grid-cols-4 mb-4">
    <div class="bg-white rounded-xl border p-4"><div class="text-xs uppercase text-slate-500">Overall</div><div class="text-lg font-bold">{{ $result['overall_status'] }}</div></div>
    <div class="bg-white rounded-xl border p-4"><div class="text-xs uppercase text-slate-500">Go-live</div><div class="text-lg font-bold">{{ $result['go_live'] }}</div></div>
    <div class="bg-white rounded-xl border p-4"><div class="text-xs uppercase text-slate-500">Files</div><div class="text-lg font-bold">{{ $result['files']['recognized'] }}/6</div></div>
    <div class="bg-white rounded-xl border p-4"><div class="text-xs uppercase text-slate-500">Unresolved masters</div><div class="text-lg font-bold">{{ $result['master_mapping']['unresolved'] ?? 0 }}</div></div>
</div>
<div class="grid gap-4 md:grid-cols-2 mb-4">
<div class="bg-white rounded-xl border p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Readiness (/10)</h3>
<table class="w-full text-sm">@foreach($result['readiness'] as $k=>$v)<tr class="border-t border-slate-100"><td class="py-1">{{ $k }}</td><td class="py-1 text-right font-semibold">{{ $v }}</td></tr>@endforeach</table></div>
<div class="bg-white rounded-xl border p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Scope totals</h3>
<table class="w-full text-sm"><thead><tr class="text-left text-xs text-slate-500"><th>Scope</th><th>Rows</th><th>Err</th><th>Match</th><th>Var</th></tr></thead><tbody>
@foreach(['sales','deposit','finance','payroll','spareparts','documents'] as $s)
<tr class="border-t border-slate-100"><td class="py-1">{{ $s }}</td><td class="py-1">{{ $result[$s]['rows'] ?? 0 }}</td><td class="py-1">{{ $result[$s]['error'] ?? 0 }}</td><td class="py-1">{{ $result[$s]['match'] ?? 0 }}</td><td class="py-1">{{ $result[$s]['variance'] ?? 0 }}</td></tr>
@endforeach</tbody></table>
<div class="flex gap-2 mt-3">
<a href="{{ route('bfj.acceptance.json', ['batches' => implode(',', $ids)]) }}" class="px-3 py-2 rounded-lg border text-xs">Download JSON</a>
<a href="{{ route('bfj.acceptance.xlsx', ['batches' => implode(',', $ids)]) }}" class="px-3 py-2 rounded-lg border text-xs">Download Excel</a>
</div></div>
</div>
<div class="bg-white rounded-xl border p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Data quality</h3>
<table class="w-full text-xs"><tbody>@foreach($result['data_quality'] as $k=>$v)<tr class="border-t border-slate-100"><td class="py-1 font-mono">{{ $k }}</td><td class="py-1 text-right">{{ $v }}</td></tr>@endforeach</tbody></table></div>
@endsection
