{{-- Feature grid: only implemented + marketing_enabled features. --}}
@props(['features'])
@if($features !== [])
<section aria-labelledby="fitur" class="max-w-6xl mx-auto px-4 mt-12">
    <h2 id="fitur" class="text-2xl font-bold tracking-tight">Fitur yang Termasuk</h2>
    <p class="mt-1 text-sm text-slate-500">Seluruh fitur di bawah ini benar-benar tersedia di source code.</p>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($features as $f)
        <article class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="font-semibold">{{ $f['name'] }}</h3>
            <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">{{ $f['short_description'] }}</p>
            <a href="/modul/{{ $f['slug'] }}" class="mt-3 inline-block text-sm font-semibold text-amber-600 hover:text-amber-700">Pelajari fitur →</a>
        </article>
        @endforeach
    </div>
</section>
@endif
