@extends('layouts.seo')

@section('content')
<section class="max-w-6xl mx-auto px-4 pt-10">
    <p class="text-xs font-bold tracking-[.15em] uppercase text-amber-600">Solution Finder</p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Cari Solusi ERP Tambang</h1>
    <p class="mt-2 text-slate-500 max-w-2xl">Pilih industri, modul, atau lokasi — kami tunjukkan halaman yang paling relevan. Source code mulai Rp12 juta.</p>

    <form method="GET" action="/cari-solusi" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 grid gap-4 sm:grid-cols-4">
        <label class="block text-sm font-medium">Industri
            <select name="industry" class="mt-1 w-full min-h-[44px] rounded-lg border border-slate-300 px-3">
                <option value="">Semua industri</option>
                @foreach($industries as $i)<option value="{{ $i->slug }}" @selected(request('industry') === $i->slug)>{{ $i->name }}</option>@endforeach
            </select>
        </label>
        <label class="block text-sm font-medium">Modul
            <select name="feature" class="mt-1 w-full min-h-[44px] rounded-lg border border-slate-300 px-3">
                <option value="">Semua modul</option>
                @foreach($features as $f)<option value="{{ $f->slug }}" @selected(request('feature') === $f->slug)>{{ $f->name }}</option>@endforeach
            </select>
        </label>
        <label class="block text-sm font-medium">Lokasi
            <select name="location" class="mt-1 w-full min-h-[44px] rounded-lg border border-slate-300 px-3">
                <option value="">Semua lokasi</option>
                @foreach($locations as $l)<option value="{{ $l->slug }}" @selected(request('location') === $l->slug)>{{ $l->name }}</option>@endforeach
            </select>
        </label>
        <div class="flex items-end">
            <button class="w-full min-h-[44px] rounded-lg bg-[#0f172a] text-white font-semibold">Cari</button>
        </div>
    </form>

    @if(request()->anyFilled(['industry', 'feature', 'location']))
    <h2 class="mt-8 text-xl font-bold">Hasil ({{ $results->count() }})</h2>
    @if($results->isEmpty())
    <p class="mt-2 text-slate-500">Belum ada halaman untuk kombinasi ini. Coba filter lain atau <a href="https://wa.me/6281296052010" class="text-amber-600 font-semibold">tanya via WhatsApp</a>.</p>
    @else
    <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($results as $r)
        <li><a href="{{ $r->url() }}" class="block rounded-xl border border-slate-200 bg-white px-5 py-4 hover:border-amber-400"><span class="block font-semibold leading-snug">{{ $r->title }}</span><span class="text-xs text-slate-400">{{ $r->keyword }}</span></a></li>
        @endforeach
    </ul>
    @endif
    @endif
</section>
@endsection
