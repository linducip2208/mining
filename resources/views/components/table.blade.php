<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            @isset($head)
            <thead class="bg-slate-50 border-b border-slate-200 sticky top-0">
                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500">
                    {{ $head }}
                </tr>
            </thead>
            @endisset
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    @isset($footer)
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50 text-sm text-slate-500">
            {{ $footer }}
        </div>
    @endisset
</div>
