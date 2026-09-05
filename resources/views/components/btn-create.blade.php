@props(['label' => 'Buat Baru', 'href' => '#'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold shadow-sm']) }}>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
    {{ $label }}
</a>
