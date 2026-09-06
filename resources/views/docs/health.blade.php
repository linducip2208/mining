@extends('layouts.docs')

@section('meta-title', 'Kesehatan Dokumentasi')

@section('breadcrumb')
<span class="mx-1" aria-hidden="true">/</span><span class="text-slate-700 dark:text-slate-200 font-medium" aria-current="page">Health</span>
@endsection

@section('content')
<x-ui.page-header title="Kesehatan Dokumentasi" description="Indikator kelengkapan internal — hanya Super Admin." />

<div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
    <x-ui.stat label="Total Halaman" :value="$total" icon="book-open" />
    <x-ui.stat label="Tanpa Screenshot" :value="count($noShot)" icon="eye" />
    <x-ui.stat label="Tanpa Permission" :value="count($noPerm)" icon="shield" />
    <x-ui.stat label="Relasi Rusak" :value="count($badRelated)" icon="link" />
    <x-ui.stat label="Mapping Rusak" :value="count($badMapping)" icon="flag" />
</div>

@foreach (['Halaman tanpa screenshot' => $noShot, 'Halaman tanpa permission' => $noPerm, 'Tautan terkait rusak' => $badRelated, 'Mapping route rusak' => $badMapping] as $title => $rows)
<x-ui.card :title="$title" :subtitle="count($rows) . ' temuan'" class="mt-4">
    @if (empty($rows))
    <x-ui.empty-state icon="check-circle" title="Bersih" body="Tidak ada temuan pada kategori ini." />
    @else
    <ul class="text-[13px] font-mono space-y-1 max-h-64 overflow-y-auto nice-scroll">
        @foreach ($rows as $row)<li class="px-2 py-1 rounded bg-slate-50 dark:bg-white/5 truncate" title="{{ $row }}">{{ $row }}</li>@endforeach
    </ul>
    @endif
</x-ui.card>
@endforeach
@endsection
