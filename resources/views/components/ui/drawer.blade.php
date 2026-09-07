{{-- Right drawer for quick view / approval / audit detail. Open via $dispatch('open-drawer', 'name').
     Desktop/tablet: side panel. Mobile (<640px): bottom sheet, near-full width. --}}
@props(['name' => 'drawer', 'title' => null])
<div x-data="{ open: false }"
     x-on:open-drawer.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:keydown.escape.window="if (open) open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-slate-900/50" @click="open = false"></div>
    <aside x-show="open" x-cloak
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full sm:translate-x-full translate-y-full sm:translate-y-0" x-transition:enter-end="translate-x-0 translate-y-0"
           x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 translate-y-0" x-transition:leave-end="translate-x-full sm:translate-x-full translate-y-full sm:translate-y-0"
           class="fixed inset-x-0 bottom-0 sm:inset-y-0 sm:left-auto sm:right-0 z-50 sm:w-full max-w-md h-[88dvh] sm:h-auto rounded-t-2xl sm:rounded-none bg-white dark:bg-navy-800 shadow-pop flex flex-col"
           role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 shrink-0">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 min-w-0">{{ $title }}</h3>
            <x-ui.icon-button icon="x" label="Tutup panel" @click="open = false" />
        </div>
        <div class="flex-1 overflow-y-auto nice-scroll p-5">{{ $slot }}</div>
    </aside>
</div>
