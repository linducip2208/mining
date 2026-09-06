{{-- Page header: breadcrumb + title + description + actions. --}}
@props(['title', 'description' => null, 'breadcrumbs' => []])
<header class="mb-5">
    @if ($breadcrumbs)
    <x-ui.breadcrumb :items="$breadcrumbs" class="mb-1.5" />
    @endif
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl lg:text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">{{ $title }}</h1>
            @if ($description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-2xl">{{ $description }}</p>
            @endif
        </div>
        @if (trim($actions ?? '') !== '')
        <div class="flex flex-wrap items-center gap-2 print:hidden">{{ $actions }}</div>
        @endif
    </div>
</header>
