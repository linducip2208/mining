@props(['title', 'value', 'sub' => null, 'color' => 'slate', 'icon' => null])
<div class="bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-4">
    <div class="flex items-start justify-between gap-2">
        <div class="text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">{{ $title }}</div>
        @if ($icon)
        <span class="w-8 h-8 flex-none rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <x-ui.icon :name="$icon" class="w-4 h-4" />
        </span>
        @endif
    </div>
    <div class="mt-1 text-[22px] leading-7 font-bold tracking-tight text-slate-800 dark:text-slate-50">{{ $value }}</div>
    @if ($sub)
        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $sub }}</div>
    @endif
</div>
