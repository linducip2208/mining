@props(['route', 'label' => 'Filter'])
<form method="GET" action="{{ $route }}" {{ $attributes->merge(['class' => 'bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-4 mb-4 flex flex-wrap gap-3 items-end print:hidden']) }}>
    {{ $slot }}
    <button type="submit" class="px-4 py-2 rounded-ctl bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium">{{ $label }}</button>
    @if (count(array_filter(request()->query())))
        <a href="{{ $route }}" class="px-4 py-2 rounded-ctl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 text-sm">Reset</a>
    @endif
</form>
