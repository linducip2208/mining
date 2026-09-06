{{-- Card: consistent 12px radius, hairline border, minimal shadow. --}}
@props(['title' => null, 'subtitle' => null, 'padding' => true])
<section {{ $attributes->merge(['class' => 'bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card']) }}>
    @if ($title || trim($actions ?? '') !== '')
    <div class="flex items-start justify-between gap-3 px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
        <div class="min-w-0">
            @if ($title)<h2 class="font-semibold text-[15px] text-slate-800 dark:text-slate-100">{{ $title }}</h2>@endif
            @if ($subtitle)<p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $subtitle }}</p>@endif
        </div>
        @if (trim($actions ?? '') !== '')
        <div class="flex items-center gap-2 shrink-0 print:hidden">{{ $actions }}</div>
        @endif
    </div>
    @endif
    <div class="{{ $padding ? 'p-5' : '' }}">{{ $slot }}</div>
</section>
