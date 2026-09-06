{{-- Breadcrumb: items = [['label'=>, 'href'=>?]] --}}
@props(['items' => []])
<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-[13px] text-slate-400 dark:text-slate-500']) }}>
    <ol class="flex flex-wrap items-center gap-1">
        @foreach ($items as $i => $it)
        <li class="flex items-center gap-1 min-w-0">
            @if ($i > 0)<span aria-hidden="true" class="text-slate-300 dark:text-slate-600">/</span>@endif
            @if (!empty($it['href']) && !$loop->last)
            <a href="{{ $it['href'] }}" class="hover:text-amber-600 truncate">{{ $it['label'] }}</a>
            @else
            <span @if($loop->last) aria-current="page" @endif class="{{ $loop->last ? 'text-slate-600 dark:text-slate-300 font-medium truncate' : '' }}">{{ $it['label'] }}</span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
