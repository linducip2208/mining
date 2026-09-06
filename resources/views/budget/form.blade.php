@extends('layouts.app')

@section('title', ' - Buat Budget')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Budget</h1>
</div>

<form method="POST" action="{{ route('budgets.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-5xl">
    @csrf
    <div class="grid md:grid-cols-4 gap-4 mb-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}" @selected(old('company_id') == $id)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site (opsional)</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">Pusat</option>
                @foreach ($sites ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tahun</label>
            <input type="number" min="2000" max="2100" name="year" value="{{ old('year', now()->year) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="OPEX">OPEX</option>
                <option value="CAPEX">CAPEX</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Divisi (opsional)</label>
            <select name="division_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($divisions ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Departemen (opsional)</label>
            <select name="department_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($departments ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Cost Center (opsional)</label>
            <select name="cost_center_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($costCenters ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
            <input type="text" name="notes" value="{{ old('notes') }}" maxlength="2000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>

    <div class="text-sm font-bold text-slate-700 mb-2">Detail Pagu per COA</div>
    <div id="budget-lines" class="space-y-2">
        <div class="grid grid-cols-4 gap-2 items-center bg-slate-50 rounded-lg px-3 py-2">
            <select name="lines[0][chart_of_account_id]" required class="px-2 py-1.5 rounded border border-slate-200 bg-white text-sm">
                <option value="">COA...</option>
                @foreach ($coas ?? [] as $c)
                    <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                @endforeach
            </select>
            <select name="lines[0][cost_center_id]" class="px-2 py-1.5 rounded border border-slate-200 bg-white text-sm">
                <option value="">CC...</option>
                @foreach ($costCenters ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
            <input type="month" name="lines[0][period]" class="px-2 py-1.5 rounded border border-slate-200 text-sm">
            <input type="number" step="0.01" min="0" name="lines[0][amount]" required placeholder="Pagu (Rp)" class="px-2 py-1.5 rounded border border-slate-200 text-sm">
        </div>
    </div>
    <button type="button" id="add-line" class="mt-2 text-xs text-indigo-600 hover:underline">+ Tambah baris</button>

    <script>
    (function () {
        var idx = 1;
        var coaOptions = document.querySelector('select[name="lines[0][chart_of_account_id]"]').innerHTML;
        var ccOptions = document.querySelector('select[name="lines[0][cost_center_id]"]').innerHTML;
        document.getElementById('add-line').addEventListener('click', function () {
            var div = document.createElement('div');
            div.className = 'grid grid-cols-4 gap-2 items-center bg-slate-50 rounded-lg px-3 py-2';
            div.innerHTML = '<select name="lines[' + idx + '][chart_of_account_id]" required class="px-2 py-1.5 rounded border border-slate-200 bg-white text-sm">' + coaOptions + '</select>'
                + '<select name="lines[' + idx + '][cost_center_id]" class="px-2 py-1.5 rounded border border-slate-200 bg-white text-sm">' + ccOptions + '</select>'
                + '<input type="month" name="lines[' + idx + '][period]" class="px-2 py-1.5 rounded border border-slate-200 text-sm">'
                + '<input type="number" step="0.01" min="0" name="lines[' + idx + '][amount]" required placeholder="Pagu (Rp)" class="px-2 py-1.5 rounded border border-slate-200 text-sm">';
            document.getElementById('budget-lines').appendChild(div);
            idx++;
        });
    })();
    </script>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Draft</button>
        <a href="{{ route('budgets.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
