{{-- Problem → solution narrative. --}}
@props(['problems', 'solution'])
<section aria-labelledby="masalah" class="max-w-6xl mx-auto px-4 mt-12 grid gap-8 md:grid-cols-2">
    <div>
        <h2 id="masalah" class="text-2xl font-bold tracking-tight">Masalah Operasional yang Umum</h2>
        <ul class="mt-4 space-y-2.5 text-sm leading-relaxed">
            @foreach($problems as $problem)
            <li class="flex gap-2.5">
                <span class="mt-0.5 w-5 h-5 shrink-0 rounded-full bg-red-100 text-red-600 grid place-items-center text-xs font-bold" aria-hidden="true">!</span>
                <span>{{ $problem }}</span>
            </li>
            @endforeach
        </ul>
    </div>
    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-6">
        <h2 class="text-xl font-bold tracking-tight text-emerald-900">Solusi Terintegrasi</h2>
        <p class="mt-2 text-sm text-emerald-900/80 leading-relaxed">{{ $solution }}</p>
    </div>
</section>
