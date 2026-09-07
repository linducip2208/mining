{{-- Source code benefits + honest licensing note. --}}
@props(['benefits'])
<section aria-labelledby="source-code" class="max-w-6xl mx-auto px-4 mt-12">
    <h2 id="source-code" class="text-2xl font-bold tracking-tight">Kenapa Memilih Source Code?</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach(array_slice($benefits, 0, 5) as $b)
        <article class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="font-semibold">{{ $b['title'] }}</h3>
            <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">{{ $b['body'] }}</p>
        </article>
        @endforeach
        <article class="rounded-xl border border-amber-300 bg-amber-50 p-5">
            <h3 class="font-semibold">Lisensi Jelas</h3>
            <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">Detail lisensi source code mengikuti penawaran/perjanjian — tanpa janji resale, redistribusi, maupun transfer copyright yang belum disepakati.</p>
        </article>
    </div>
</section>
