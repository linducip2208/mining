{{-- Related + hub links (max 12 contextual). --}}
@props(['related', 'hub' => []])
@if($related !== [] || $hub !== [])
<nav aria-label="Halaman terkait" class="max-w-6xl mx-auto px-4 mt-12">
    <h2 class="text-2xl font-bold tracking-tight">Jelajahi Juga</h2>
    <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach(array_slice($related, 0, 12) as $link)
        <li>
            <a href="{{ $link['url'] }}" class="block rounded-xl border border-slate-200 bg-white px-5 py-4 hover:border-amber-400 transition-colors">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $link['label'] }}</span>
                <span class="block mt-1 font-semibold leading-snug">{{ $link['title'] }}</span>
            </a>
        </li>
        @endforeach
        @foreach($hub as $link)
        <li>
            <a href="{{ $link['url'] }}" class="block rounded-xl border border-slate-200 bg-white px-5 py-4 hover:border-amber-400 transition-colors">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $link['label'] }}</span>
                <span class="block mt-1 font-semibold leading-snug">{{ $link['title'] }}</span>
            </a>
        </li>
        @endforeach
    </ul>
</nav>
@endif
