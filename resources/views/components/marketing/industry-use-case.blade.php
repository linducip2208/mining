{{-- Contextual industry/location paragraph (factual-safe wording). --}}
@props(['page'])
@if($page->industry || $page->location)
<section aria-label="Konteks penggunaan" class="max-w-6xl mx-auto px-4 mt-12">
    <div class="rounded-2xl bg-white border border-slate-200 p-6 text-sm leading-relaxed text-slate-600">
        @if($page->industry)
        <h2 class="text-xl font-bold tracking-tight text-slate-800">Cocok untuk {{ $page->industry->name }}</h2>
        <p class="mt-2">{{ $page->industry->description }}</p>
        @endif
        @if($page->location)
        <h2 class="text-xl font-bold tracking-tight text-slate-800 {{ $page->industry ? 'mt-5' : '' }}">ERP Tambang untuk Perusahaan di {{ $page->location->name }}</h2>
        <p class="mt-2">Dirancang untuk operasi multi-site: manajemen terpusat, operasional site jarak jauh, data scope per site, approval lintas lokasi, dashboard manajemen, serta visibilitas fleet, BBM, dan stockpile dari kantor pusat.</p>
        @endif
    </div>
</section>
@endif
