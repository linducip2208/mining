{{-- Button: primary(amber) / secondary / danger / ghost / success. Renders <a> when href given. --}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit', 'icon' => null])
@php
$base = 'inline-flex items-center justify-center gap-1.5 font-semibold rounded-ctl transition whitespace-nowrap focus-visible:outline-none disabled:opacity-50 disabled:pointer-events-none';
$sizes = ['sm' => 'px-3 py-1.5 text-xs', 'md' => 'px-4 py-2 text-sm', 'lg' => 'px-5 py-2.5 text-sm'];
$variants = [
    'primary' => 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm',
    'success' => 'bg-green-600 hover:bg-green-700 text-white shadow-sm',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-sm',
    'secondary' => 'bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-slate-300 hover:bg-slate-50 dark:hover:bg-navy-700',
    'ghost' => 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-navy-700',
    'dark' => 'bg-slate-800 hover:bg-slate-900 text-white',
];
@endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>
    @if ($icon)<x-ui.icon :name="$icon" class="w-4 h-4" />@endif{{ $slot }}
</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>
    @if ($icon)<x-ui.icon :name="$icon" class="w-4 h-4" />@endif{{ $slot }}
</button>
@endif
