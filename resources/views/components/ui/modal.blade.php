{{-- Modal: Alpine-driven, focus close button on open, ESC/outside to close.
     Desktop: centered dialog. Mobile (<640px): bottom sheet, near-full width. --}}
@props(['title' => null, 'wide' => false])
<div x-data="{ open: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $attributes->get('name', 'modal') }}') open = true"
     x-on:keydown.escape.window="if (open) open = false"
     {{ $attributes->only('class') }}>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <div class="absolute inset-0 bg-slate-900/60" @click="open = false"></div>
        <div x-show="open" x-transition.scale.95 x-transition.opacity
             class="relative w-full {{ $wide ? 'sm:max-w-3xl' : 'sm:max-w-lg' }} max-h-[92dvh] bg-white dark:bg-navy-800 rounded-t-2xl sm:rounded-card shadow-pop animate-fadeup flex flex-col overflow-hidden">
            @if ($title)
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 shrink-0">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 min-w-0">{{ $title }}</h3>
                <x-ui.icon-button icon="x" label="Tutup" @click="open = false" />
            </div>
            @endif
            <div class="p-5 overflow-y-auto nice-scroll">{{ $slot }}</div>
        </div>
    </div>
    {{ $trigger ?? '' }}
</div>
