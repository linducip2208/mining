{{-- Page header: breadcrumb + title + description + actions.
     Mobile: actions wrap to their own full-width row under the title. --}}
@props(['title', 'description' => null, 'breadcrumbs' => []])
<header class="mb-5">
    @if ($breadcrumbs)
    <x-ui.breadcrumb :items="$breadcrumbs" class="mb-1.5" />
    @endif
    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-start sm:justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl lg:text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 break-words">{{ $title }}</h1>
            @if ($description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-2xl">{{ $description }}</p>
            @endif
        </div>
        @if (trim($actions ?? '') !== '')
        <div class="flex flex-wrap items-center gap-2 print:hidden max-sm:w-full">{{ $actions }}</div>
        @endif
    </div>
</header>
