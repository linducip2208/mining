<div class="bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card overflow-hidden">
    <div class="overflow-x-auto nice-scroll">
        <table class="w-full text-sm">
            @isset($head)
            <thead class="bg-slate-50 dark:bg-navy-900/60 border-b border-slate-200 dark:border-slate-700/60">
                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    {{ $head }}
                </tr>
            </thead>
            @endisset
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-slate-700 dark:text-slate-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    @isset($footer)
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-navy-900/40 text-sm text-slate-500 dark:text-slate-400">
            {{ $footer }}
        </div>
    @endisset
</div>
