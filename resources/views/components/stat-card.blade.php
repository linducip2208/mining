@props(['title', 'value', 'sub' => null, 'color' => 'slate', 'icon' => null])
@php
    $bg = ['amber'=>'bg-amber-50 border-amber-200','green'=>'bg-green-50 border-green-200','blue'=>'bg-blue-50 border-blue-200','red'=>'bg-red-50 border-red-200','slate'=>'bg-slate-50 border-slate-200','indigo'=>'bg-indigo-50 border-indigo-200'][$color];
@endphp
<div class="rounded-xl border {{ $bg }} p-4">
    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">{{ $title }}</div>
    <div class="mt-1 text-2xl font-bold text-slate-800">{{ $value }}</div>
    @if ($sub)
        <div class="text-xs text-slate-500 mt-0.5">{{ $sub }}</div>
    @endif
</div>
