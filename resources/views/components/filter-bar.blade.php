{{-- Filter bar: inline row on desktop; collapsible panel on mobile.
     Open via the "Filter" toggle; fields stack full-width on small screens. --}}
@props(['route', 'label' => 'Filter'])
@php $hasQuery = count(array_filter(request()->query())) > 0; @endphp
<form method="GET" action="{{ $route }}" x-data="{ open: {{ $hasQuery ? 'true' : 'false' }} }"
    {{ $attributes->merge(['class' => 'bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-4 mb-4 print:hidden']) }}>
    {{-- Mobile toggle row --}}
    <div class="flex items-center justify-between gap-3 sm:hidden">
        <button type="button" @click="open = !open" class="inline-flex items-center gap-2 min-h-[42px] px-3.5 rounded-ctl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-white/5 text-sm font-semibold text-slate-700 dark:text-slate-200" :aria-expanded="open ? 'true' : 'false'">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="w-4 h-4"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
            {{ $label }}
        </button>
        @if ($hasQuery)
            <a href="{{ $route }}" class="inline-flex items-center min-h-[42px] px-3 rounded-ctl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 text-sm">Reset</a>
        @endif
    </div>

    <div class="hidden sm:flex sm:flex-row sm:flex-wrap sm:items-end sm:gap-3" :class="open && 'flex !flex-col mt-3 sm:mt-0 sm:!flex-row'">
        {{ $slot }}
        <div class="flex gap-2 items-center max-sm:w-full max-sm:pt-1">
            <button type="submit" class="px-4 min-h-[42px] rounded-ctl bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium max-sm:flex-1">{{ $label === 'Filter' ? 'Terapkan' : $label }}</button>
            @if ($hasQuery)
                <a href="{{ $route }}" class="px-4 min-h-[42px] inline-flex items-center rounded-ctl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 text-sm max-sm:flex-1 max-sm:justify-center">Reset</a>
            @endif
        </div>
    </div>
</form>
