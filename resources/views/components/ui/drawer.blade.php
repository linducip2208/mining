{{-- Right drawer for quick view / approval / audit detail. Open via $dispatch('open-drawer', 'name'). --}}
@props(['name' => 'drawer', 'title' => null])
<div x-data="{ open: false }"
     x-on:open-drawer.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-slate-900/50" @click="open = false"></div>
    <aside x-show="open" x-cloak
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white dark:bg-navy-800 shadow-pop flex flex-col" role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700/60">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
            <x-ui.icon-button icon="x" label="Tutup panel" @click="open = false" />
        </div>
        <div class="flex-1 overflow-y-auto nice-scroll p-5">{{ $slot }}</div>
    </aside>
</div>
