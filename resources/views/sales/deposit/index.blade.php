@extends('layouts.app')
@section('title', ' - Deposit Customer')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Deposit Customer (Ledger)</h1>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Deposit Masuk</h3>
        <form method="POST" action="{{ route('deposit.in') }}">
            @csrf
            <div class="space-y-3">
                <select name="company_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach (\App\Models\Company::all() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <select name="customer_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">-- Customer --</option>@foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <input type="number" step="0.01" name="amount" placeholder="Jumlah (Rp)" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                <input type="date" name="deposit_date" value="{{ today()->toDateString() }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                <select name="cash_account_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">-- Kas/Bank --</option>@foreach ($cashAccounts as $ca)<option value="{{ $ca->id }}">{{ $ca->name }}</option>@endforeach</select>
                <input name="reference_no" placeholder="No referensi" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                <button class="w-full px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold">Terima Deposit</button>
            </div>
        </form>
        <hr class="my-4">
        <h3 class="font-semibold text-sm mb-2 text-orange-600">Refund</h3>
        <form method="POST" action="{{ route('deposit.refund') }}" onsubmit="return confirm('Proses refund?')">
            @csrf
            <div class="space-y-3">
                <select name="company_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach (\App\Models\Company::all() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <select name="customer_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">-- Customer --</option>@foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <input type="number" step="0.01" name="amount" placeholder="Jumlah (Rp)" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                <input type="date" name="deposit_date" value="{{ today()->toDateString() }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                <select name="cash_account_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">-- Kas/Bank --</option>@foreach ($cashAccounts as $ca)<option value="{{ $ca->id }}">{{ $ca->name }}</option>@endforeach</select>
                <button class="w-full px-4 py-2 rounded-lg bg-orange-500 text-white text-sm font-semibold">Refund Deposit</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden lg:col-span-2">
        <div class="px-5 py-3 border-b border-slate-100 font-semibold text-sm">Saldo per Customer</div>
        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-sm">
                <thead class="bg-slate-50"><tr class="text-left text-[11px] uppercase text-slate-500"><th class="px-4 py-2">Customer</th><th class="px-4 py-2 text-right">Saldo Deposit</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($balances as $b)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2">{{ $b->name }}</td>
                        <td class="px-4 py-2 text-right font-semibold {{ $b->deposit_balance > 0 ? 'text-green-700' : 'text-slate-400' }}">Rp {{ number_format($b->deposit_balance, 0, ',', '.') }}</td>
                        <td class="px-4 py-2"><a href="{{ route('deposit.statement', $b->id) }}" class="text-amber-600 text-xs hover:underline">Statement</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada customer</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 mt-4">
    <div class="px-5 py-3 border-b border-slate-100 font-semibold text-sm">Pergerakan Terakhir (Ledger)</div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr class="text-left text-[11px] uppercase text-slate-500">
            <th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2">Tipe</th><th class="px-4 py-2 text-right">Jumlah</th><th class="px-4 py-2">Ref</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($movements as $m)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2">{{ $m->deposit_date?->format('d/m/Y') }}</td>
                <td class="px-4 py-2">{{ $m->customer?->name }}</td>
                <td class="px-4 py-2"><x-status-badge :status="$m->movement_type" /></td>
                <td class="px-4 py-2 text-right font-medium">Rp {{ number_format($m->amount, 0, ',', '.') }}</td>
                <td class="px-4 py-2 text-xs">{{ $m->ref_number }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada pergerakan deposit</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection