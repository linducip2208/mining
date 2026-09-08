@extends('layouts.app')
@section('title', ' - BFJ Batch')
@section('content')
<div class="mb-4"><a href="{{ route('bfj.index') }}" class="text-xs text-slate-500">← Migration Dashboard</a>
    <h1 class="text-xl font-bold text-slate-800">Batch #{{ $batch->id }} <span class="text-xs font-mono bg-slate-100 px-2 py-1 rounded">{{ $batch->status }}</span></h1>
    <p class="text-sm text-slate-500 font-mono">{{ $batch->file_name }} · {{ $batch->mode }} · hash {{ substr($batch->file_hash,0,12) }}</p>
</div>
<div class="grid gap-4 md:grid-cols-3 mb-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Impact simulation</h3>
        <dl class="text-sm">@foreach($impact as $k=>$v)<div class="flex justify-between py-0.5"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-semibold">{{ is_bool($v)?($v?'yes':'no'):$v }}</dd></div>@endforeach</dl></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Report</h3>
        <dl class="text-sm">@foreach($report as $k=>$v)<div class="flex justify-between py-0.5"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-semibold">{{ is_array($v)?json_encode($v):$v }}</dd></div>@endforeach</dl></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Actions</h3>
        <form method="POST" action="{{ route('bfj.import', $batch) }}" class="grid gap-2">
            @csrf
            <label class="text-xs flex items-center gap-2"><input type="checkbox" name="allow_accounting_posting" value="1"> Allow accounting posting (Finance auth)</label>
            <label class="text-xs flex items-center gap-2"><input type="checkbox" name="allow_stock_posting" value="1"> Allow stock posting (explicit cut-off)</label>
            <label class="text-xs flex items-center gap-2"><input type="checkbox" name="confirm_impact" value="1" required> I confirm the impact above</label>
            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">IMPORT</button>
        </form>
        <div class="flex gap-2 mt-2 flex-wrap">
            <form method="POST" action="{{ route('bfj.reconcile', $batch) }}">@csrf<button class="px-3 py-2 rounded-lg border text-xs">RECONCILE</button></form>
            <form method="POST" action="{{ route('bfj.rollback', $batch) }}">@csrf<button class="px-3 py-2 rounded-lg border text-xs text-red-600">ROLLBACK</button></form>
            <a href="{{ route('bfj.mapping', $batch) }}" class="px-3 py-2 rounded-lg border text-xs">MASTER MAPPING →</a>
            <a href="{{ route('bfj.acceptance', ['batches' => $batch->id]) }}" class="px-3 py-2 rounded-lg border text-xs">REPORT CARD →</a>
        </div>
        <form method="POST" action="{{ route('bfj.close', $batch) }}" class="grid gap-2 mt-3 border-t border-slate-100 pt-3">
            @csrf
            <div class="text-xs font-semibold uppercase text-slate-500">Close batch @if($batch->status==='CLOSED')<span class="text-green-700">(CLOSED {{ $batch->closed_at }})</span>@endif</div>
            <input type="text" name="close_note" placeholder="Close note" class="text-xs border rounded px-2 py-2">
            <input type="text" name="exception_reason" placeholder="Exception reason (jika ada blocker)" class="text-xs border rounded px-2 py-2">
            <input type="text" name="exception_approver" placeholder="Exception approver" class="text-xs border rounded px-2 py-2">
            <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs">CLOSE BATCH</button>
        </form>
        <form method="POST" action="{{ route('bfj.signoff', $batch) }}" class="flex gap-2 mt-2">
            @csrf
            <select name="role" class="text-xs border rounded px-2 py-2"><option value="prepared">Prepared</option><option value="reviewed">Reviewed</option><option value="finance">Finance</option><option value="warehouse">Warehouse</option><option value="hr">HR</option><option value="management">Management</option></select>
            <input type="text" name="name" placeholder="Nama" required class="text-xs border rounded px-2 py-2 flex-1">
            <button class="px-3 py-2 rounded-lg border text-xs">SIGN OFF</button>
        </form>
        @if(!empty($batch->signoffs))<div class="text-xs mt-2 text-slate-600">Sign-offs: @foreach($batch->signoffs as $r=>$s)<span class="px-1 bg-slate-100 rounded">{{ $r }}: {{ $s['name'] ?? '?' }}</span> @endforeach</div>@endif</div>
</div>
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Workbook inspector — sheets</h3>
    <table class="w-full text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th>Sheet</th><th>Detected</th><th>Conf</th><th>Rows</th><th>Summary?</th><th>Action</th><th></th></tr></thead>
    <tbody>@foreach($batch->sheets as $s)<tr class="border-t border-slate-100">
        <td class="py-1 font-medium">{{ $s->sheet_name }}</td><td class="py-1">{{ $s->detected_type }}</td><td class="py-1">{{ $s->confidence }}%</td>
        <td class="py-1">{{ $s->row_count }}</td><td class="py-1">{{ $s->is_summary?'RECONCILE_ONLY':'—' }}</td><td class="py-1">{{ $s->action }}</td>
        <td class="py-1"><form method="POST" action="{{ route('bfj.sheet', $batch) }}" class="flex gap-1">@csrf
            <input type="hidden" name="sheet_id" value="{{ $s->id }}">
            <select name="action" class="text-xs border rounded px-1 py-1">@foreach(['IMPORT','RECONCILE_ONLY','IGNORE'] as $a)<option @selected($s->action===$a)>{{ $a }}</option>@endforeach</select>
            <button class="text-xs text-indigo-600">save</button></form></td></tr>@endforeach</tbody></table></div>
<div class="grid gap-4 md:grid-cols-2">
    <div class="bg-white rounded-xl border border-slate-200 p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Issues (latest 100)</h3>
        <table class="w-full text-xs"><tbody>@foreach($batch->issues as $i)<tr class="border-t border-slate-100"><td class="py-1 font-mono">{{ $i->code }}</td><td class="py-1">{{ $i->severity }}</td><td class="py-1">{{ $i->message }}</td></tr>@endforeach</tbody></table></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><h3 class="text-xs font-semibold uppercase text-slate-500 mb-2">Reconciliation</h3>
        <table class="w-full text-xs"><thead><tr class="text-left text-slate-500"><th>Scope</th><th>Dimension</th><th>Legacy</th><th>ERP</th><th>Var</th><th>Status</th></tr></thead>
        <tbody>@foreach($batch->reconciliations as $r)<tr class="border-t border-slate-100"><td class="py-1">{{ $r->scope }}</td><td class="py-1">{{ $r->dimension }}</td><td class="py-1">{{ $r->legacy_total }}</td><td class="py-1">{{ $r->erp_total }}</td><td class="py-1">{{ $r->variance }}</td><td class="py-1">{{ $r->status }}</td></tr>@endforeach</tbody></table></div>
</div>
@endsection
