@extends('layouts.app')
@section('title', ' - BFJ Legacy Import')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">BFJ Legacy Import <span class="text-xs font-mono bg-slate-100 px-2 py-1 rounded">BFJ_LEGACY_2026</span></h1>
        <p class="text-sm text-slate-500">Upload → Detect → Classify → Map → Normalize → Validate → Preview → Dry run → Import → Reconcile. No ERP writes before IMPORT.</p>
    </div>
</div>
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="font-semibold text-sm mb-3">Upload workbook (.xlsx / .xls / .csv, multi-sheet)</h2>
    <form method="POST" action="{{ route('bfj.upload') }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-4 items-end">
        @csrf
        <div><label class="text-xs font-semibold text-slate-600 uppercase">File</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Mode</label>
            <select name="mode" class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full">
                @foreach(['HISTORY_ONLY','CREATE_DELIVERY_HISTORY','CREATE_SALES_ACCOUNTING','RECONCILIATION_ONLY','OPENING_BALANCE','CREATE_JOURNAL','PAYROLL_RECONCILIATION','CREATE_LEGACY_PAYROLL_RUN','REGISTER_ONLY'] as $m)
                <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Cut-off (optional)</label>
            <input type="date" name="cutoff_date" class="mt-1 px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] w-full"></div>
        <div><button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm min-h-[44px] w-full">Scan workbook</button></div>
    </form>
</div>
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h2 class="font-semibold text-sm mb-3">Batches</h2>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-slate-500"><th>ID</th><th>File</th><th>Sheets</th><th>Mode</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($batches as $b)
            <tr class="border-t border-slate-100"><td class="py-2">#{{ $b->id }}</td><td class="py-2 font-mono text-xs">{{ basename($b->file_name) }}</td>
            <td class="py-2">{{ $b->sheets_count }}</td><td class="py-2">{{ $b->mode }}</td>
            <td class="py-2"><span class="px-2 py-1 rounded bg-slate-100 text-xs">{{ $b->status }}</span></td>
            <td class="py-2"><a href="{{ route('bfj.show', $b) }}" class="text-indigo-600 text-xs font-semibold">INSPECT →</a></td></tr>
        @endforeach</tbody>
    </table>
</div>
@endsection
