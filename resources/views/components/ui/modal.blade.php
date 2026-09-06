{{-- Modal: Alpine-driven, focus close button on open, ESC/outside to close. --}}
@props(['title' => null, 'wide' => false])
<div x-data="{ open: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $attributes->get('name', 'modal') }}') open = true"
     {{ $attributes->only('class') }}>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <div class="absolute inset-0 bg-slate-900/60" @click="open = false"></div>
        <div x-show="open" x-transition.scale.95 x-transition.opacity
             class="relative w-full {{ $wide ? 'max-w-3xl' : 'max-w-lg' }} bg-white dark:bg-navy-800 rounded-card shadow-pop animate-fadeup max-h-[90vh] overflow-y-auto nice-scroll">
            @if ($title)
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700/60">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                <x-ui.icon-button icon="x" label="Tutup" @click="open = false" />
            </div>
            @endif
            <div class="p-5">{{ $slot }}</div>
        </div>
    </div>
    {{ $trigger ?? '' }}
</div>
