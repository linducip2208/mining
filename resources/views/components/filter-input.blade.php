@props(['name', 'label', 'type' => 'text', 'options' => [], 'placeholder' => ''])
@php
    $humanLabel = \App\Support\HumanLabel::label($label);
    $isStatusFilter = str_contains(strtolower($name), 'status');
@endphp
<div class="flex flex-col gap-1 {{ $attributes->only('class') }}">
    <label for="{{ $name }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 tracking-wide">{{ $humanLabel }}</label>
    @if ($type === 'select')
        <select name="{{ $name }}" id="{{ $name }}" class="px-3 py-2 rounded-ctl border border-slate-200 dark:border-slate-700 text-sm bg-white dark:bg-navy-900 text-slate-800 dark:text-slate-100 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 dark:focus:ring-amber-500/20 outline-none min-w-[160px]">
            <option value="">{{ $placeholder ?: 'Semua' }}</option>
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected(request($name) == $value)>{{ $isStatusFilter ? \App\Support\StatusLabel::label($text) : \App\Support\HumanLabel::label($text) }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}"
               class="px-3 py-2 rounded-ctl border border-slate-200 dark:border-slate-700 text-sm bg-white dark:bg-navy-900 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 dark:focus:ring-amber-500/20 outline-none min-w-[140px]">
    @endif
</div>
