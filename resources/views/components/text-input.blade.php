@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 dark:border-slate-700 dark:bg-navy-900 dark:text-slate-100 focus:border-amber-400 focus:ring-amber-100 dark:focus:ring-amber-500/20 rounded-ctl shadow-sm']) }}>
