@extends('layouts.app')
@section('title', ' - Surat Baru')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Surat Baru</h1>
<form method="POST" action="{{ route('letters.store') }}" class="space-y-4">
    @csrf
    <div class="bg-white rounded-xl border border-slate-200 p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Jenis Surat</label>
            <select name="letter_type_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->code }} - {{ $t->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="letter_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Perihal</label>
            <input name="subject" required maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Isi Ringkas</label>
            <textarea name="body" rows="3" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></textarea></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach (\App\Models\Company::orderBy('name')->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($sites as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Departemen</label>
            <select name="department_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach (\App\Models\Department::orderBy('name')->get() as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe Tujuan</label>
            <select name="recipient_type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach (['CUSTOMER' => 'Customer', 'SUPPLIER' => 'Supplier', 'EMPLOYEE' => 'Karyawan', 'GOVERNMENT' => 'Pemerintah', 'INTERNAL_DEPARTMENT' => 'Departemen Internal', 'OTHER' => 'Lainnya'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Nama Tujuan</label>
            <input name="recipient_name" maxlength="200" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan Tujuan</label>
            <input name="recipient_company" maxlength="200" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Alamat Tujuan</label>
            <textarea name="recipient_address" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></textarea></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Telepon</label>
            <input name="recipient_phone" maxlength="50" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Email</label>
            <input name="recipient_email" type="email" maxlength="150" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Customer (opsional)</label>
            <select name="customer_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Supplier (opsional)</label>
            <select name="supplier_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Karyawan (opsional)</label>
            <select name="employee_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Keterangan</label>
            <input name="notes" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div class="form-actions-sticky flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white">
        <button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Simpan Draft</button>
        <a href="{{ route('letters.index') }}" class="px-5 py-2.5 rounded-lg bg-slate-100 text-sm min-h-[44px] inline-flex items-center">Batal</a>
    </div>
</form>
@endsection
