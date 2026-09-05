@extends('layouts.app')
@section('title', ' - Jurnal Manual')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Jurnal Manual (Wajib Balance)</h1>
<form method="POST" action="{{ route('journals.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-3 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="journal_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Keterangan</label>
            <input name="memo" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div id="lines" class="mt-4 space-y-2">
        <div class="flex gap-2 line items-center">
            <select name="lines[0][chart_of_account_id]" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm"><option value="">-- Akun --</option>@foreach ($coas as $c)<option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>@endforeach</select>
            <input name="lines[0][debit]" type="number" step="0.01" placeholder="Debit" class="w-36 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <input name="lines[0][credit]" type="number" step="0.01" placeholder="Kredit" class="w-36 px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
        <div class="flex gap-2 line items-center">
            <select name="lines[1][chart_of_account_id]" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm"><option value="">-- Akun --</option>@foreach ($coas as $c)<option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>@endforeach</select>
            <input name="lines[1][debit]" type="number" step="0.01" placeholder="Debit" class="w-36 px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <input name="lines[1][credit]" type="number" step="0.01" placeholder="Kredit" class="w-36 px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
    </div>
    <button type="button" onclick="addLine()" class="mt-2 text-xs text-amber-600">+ Tambah baris</button>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Posting Jurnal</button>
        <a href="{{ route('journals.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@push('scripts')
<script>
let c = 2;
function addLine() {
    const el = document.querySelector('.line').cloneNode(true);
    el.querySelectorAll('select,input').forEach(i => { i.name = i.name.replace(/\[\d+\]/, '[' + c + ']'); if (i.tagName === 'INPUT') i.value = ''; });
    document.getElementById('lines').appendChild(el);
    c++;
}
</script>
@endpush
@endsection