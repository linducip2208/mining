{{-- Form input with label / hint / error / required marker. --}}
@props(['name', 'label' => null, 'hint' => null, 'required' => false, 'type' => 'text'])
@php $eid = $attributes->get('id', $name); @endphp
<div>
    @if ($label)
    <x-ui.label :for="$eid" :required="$required">{{ $label }}</x-ui.label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $eid }}" value="{{ old($name, $attributes->get('value')) }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->except(['value'])->merge(['class' => 'mt-1 w-full px-3 py-2 rounded-ctl border border-slate-200 dark:border-slate-700 bg-white dark:bg-navy-900 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 dark:focus:ring-amber-500/20 outline-none' . ($errors->has($name) ? ' border-red-400' : '')]) }}>
    @if ($hint)<p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>@endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
