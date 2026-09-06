{{-- Priority badge: INFO / LOW / MEDIUM / HIGH / CRITICAL. --}}
@props(['level' => 'info'])
@php
$map = [
    'info' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-300',
    'low' => 'bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-slate-300',
    'medium' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300',
    'high' => 'bg-orange-100 dark:bg-orange-500/15 text-orange-700 dark:text-orange-300',
    'warning' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300',
    'critical' => 'bg-red-100 dark:bg-red-500/15 text-red-700 dark:text-red-200 ring-1 ring-red-300 dark:ring-red-500/40',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wide ' . ($map[strtolower($level)] ?? $map['info'])]) }}>{{ $slot }}</span>
