@extends('layouts.app')
@section('title', ' - Payroll')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Payroll Run</h1>
<form method="POST" action="{{ route('payroll-runs.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-xl">
    @csrf
    <div class="space-y-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Periode (YYYY-MM)</label>
            <input name="period" value="{{ $defaultPeriod }}" required pattern="\d{4}-\d{2}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Buat</button>
        <a href="{{ route('payroll-runs.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection