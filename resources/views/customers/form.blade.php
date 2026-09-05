@extends('layouts.app')

@section('title', ' - Customer')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Customer</h1>
</div>

<form method="POST" action="{{ $item ? route('customers.update', $item) : route('customers.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
                    <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach ($companies ?? [] as $id => $n)
                            <option value="{{ $id }}" @selected($item->company_id ?? old('company_id'))>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
                    <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
                    <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">NPWP</label>
                    <input type="text" name="npwp" value="{{ $item->npwp ?? old('npwp') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Alamat</label>
                    <textarea name="address" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">{{ $item->address ?? old('address') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kota</label>
                    <input type="text" name="city" value="{{ $item->city ?? old('city') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Telepon</label>
                    <input type="text" name="phone" value="{{ $item->phone ?? old('phone') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Email</label>
                    <input type="email" name="email" value="{{ $item->email ?? old('email') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kontak</label>
                    <input type="text" name="contact_person" value="{{ $item->contact_person ?? old('contact_person') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Termin Pembayaran</label>
                    <select name="payment_term_id"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach ($paymentTerms ?? [] as $id => $n)
                            <option value="{{ $id }}" @selected($item->payment_term_id ?? old('payment_term_id'))>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Limit Kredit</label>
                    <input type="number" name="credit_limit" value="{{ $item->credit_limit ?? old('credit_limit') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Grup</label>
                    <input type="text" name="group" value="{{ $item->group ?? old('group') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <label class="flex items-center gap-2 md:col-span-2">
                    <input type="checkbox" name="status" value="1" @checked(old('status', $item->status ?? true)) class="rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                    <span class="text-sm">Aktif</span>
                </label>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('customers.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection