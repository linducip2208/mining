{{-- Form select with label / hint / error. Options: [value => text]. --}}
@props(['name', 'label' => null, 'hint' => null, 'required' => false, 'options' => [], 'placeholder' => '-- Pilih --', 'selected' => null])
<div>
    @if ($label)<x-ui.label :for="$name" :required="$required">{{ $label }}</x-ui.label>@endif
    <select name="{{ $name }}" id="{{ $name }}" {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'mt-1 w-full px-3 py-2 rounded-ctl border border-slate-200 dark:border-slate-700 bg-white dark:bg-navy-900 text-sm text-slate-800 dark:text-slate-100 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 dark:focus:ring-amber-500/20 outline-none']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $v => $t)
        <option value="{{ $v }}" @selected((string) old($name, $selected) === (string) $v)>{{ $t }}</option>
        @endforeach
    </select>
    @if ($hint)<p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>@endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
