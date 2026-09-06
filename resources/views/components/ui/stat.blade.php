{{-- KPI stat: white surface, label/value/delta/icon, optional link + sparkline data. --}}
@props(['label', 'value', 'delta' => null, 'up' => null, 'icon' => null, 'href' => null, 'sub' => null])
@php
$tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'block bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-4 transition hover:shadow-pop' . ($href ? ' hover:border-amber-300 cursor-pointer' : '')]) }}>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 truncate">{{ $label }}</div>
            <div class="mt-1 text-[22px] leading-7 font-bold tracking-tight text-slate-800 dark:text-slate-50 truncate">{{ $value }}</div>
        </div>
        @if ($icon)
        <span class="flex-none w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <x-ui.icon :name="$icon" class="w-5 h-5" />
        </span>
        @endif
    </div>
    @if ($delta !== null || $sub)
    <div class="mt-1.5 flex items-center gap-2 text-xs">
        @if ($delta !== null)
        <span class="inline-flex items-center gap-0.5 font-semibold {{ $up ? 'text-green-600 dark:text-green-400' : ($up === false ? 'text-red-500' : 'text-slate-400') }}">
            @if ($up === true)<x-ui.icon name="arrow-right" class="w-3 h-3 -rotate-45" />@elseif ($up === false)<x-ui.icon name="arrow-right" class="w-3 h-3 rotate-45" />@endif
            {{ $delta }}
        </span>
        @endif
        @if ($sub)<span class="text-slate-400 dark:text-slate-500 truncate">{{ $sub }}</span>@endif
    </div>
    @endif
</{{ $tag }}>
