@extends('layouts.app')

@section('title', ' - Sampel ' . $sample->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('samples.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">Sampel {{ $sample->number }} <x-status-badge :status="$sample->status" /></h1>
    <p class="text-sm text-slate-500">{{ $sample->sample_date }} · {{ $sample->source_type }} #{{ $sample->source_id }} · {{ $sample->item?->name }}</p>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Hasil Uji</div>
        <x-table>
            <x-slot:head>
                <th class="px-4 py-2.5">Parameter</th>
                <th class="px-4 py-2.5 text-right">Nilai</th>
                <th class="px-4 py-2.5">Verdict</th>
            </x-slot:head>
            @forelse ($sample->tests ?? [] as $t)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5">{{ $t->parameter?->code }} ({{ $t->parameter?->unit }})</td>
                <td class="px-4 py-2.5 text-right">{{ $t->result_value }}</td>
                <td class="px-4 py-2.5"><x-status-badge :status="$t->result === 'PASS' ? 'VALIDATED' : 'REJECTED'" /></td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada hasil uji</td></tr>
            @endforelse
        </x-table>

        @can('quality.test')
        <form method="POST" action="{{ route('samples.test', $sample) }}" class="mt-3 grid grid-cols-3 gap-2">
            @csrf
            <select name="quality_parameter_id" required class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">Parameter...</option>
                @foreach ($parameters ?? [] as $p)
                    <option value="{{ $p->id }}">{{ $p->code }} ({{ $p->unit }})</option>
                @endforeach
            </select>
            <input type="number" step="any" name="result_value" required placeholder="Nilai" class="px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            <button class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">Catat Hasil</button>
            <input type="text" name="notes" maxlength="1000" placeholder="Catatan (opsional)" class="col-span-3 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </form>
        @endcan
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Certificate of Analysis</div>
        <p class="text-xs text-slate-500 mb-3">Terbit setelah sampel PASS. CoA menjadi lampiran invoice / shipment.</p>
        @can('quality.approve')
        <form method="POST" action="{{ route('samples.coa', $sample) }}" class="grid grid-cols-2 gap-2">
            @csrf
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Customer</label>
                <select name="customer_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                    <option value="">-- Pilih --</option>
                    @foreach (\App\Models\Customer::where('status', true)->orderBy('name')->get() as $c)
                        <option value="{{ $c->id }}" @selected(($sample->customer_id ?? null) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase">Invoice (opsional)</label>
                <input type="number" name="invoice_id" placeholder="ID invoice" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
            </div>
            <div class="col-span-2"><button class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-semibold">Terbitkan CoA</button></div>
        </form>
        @endcan
    </div>
</div>
@endsection
