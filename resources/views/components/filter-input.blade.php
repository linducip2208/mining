@props(['name', 'label', 'type' => 'text', 'options' => [], 'placeholder' => ''])
<div class="flex flex-col gap-1 {{ $attributes->only('class') }}">
    <label for="{{ $name }}" class="text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ $label }}</label>
    @if ($type === 'select')
        <select name="{{ $name }}" id="{{ $name }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none min-w-[160px]">
            <option value="">{{ $placeholder ?: 'Semua' }}</option>
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected(request($name) == $value)>{{ $text }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}"
               class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none min-w-[140px]">
    @endif
</div>
