@props(['label' => 'Buat Baru', 'href' => '#'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-ctl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold shadow-sm print:hidden']) }}>
    <x-ui.icon name="plus" class="w-4 h-4" />
    {{ $label }}
</a>
