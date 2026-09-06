{{-- Icon button: ghost square with tooltip. --}}
@props(['icon' => 'eye', 'label' => '', 'href' => null])
@if ($href)
<a href="{{ $href }}" title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'inline-flex p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-navy-700 dark:hover:text-slate-200']) }}>
    <x-ui.icon :name="$icon" class="w-[18px] h-[18px]" />
</a>
@else
<button type="button" title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'inline-flex p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-navy-700 dark:hover:text-slate-200']) }}>
    <x-ui.icon :name="$icon" class="w-[18px] h-[18px]" />
</button>
@endif
