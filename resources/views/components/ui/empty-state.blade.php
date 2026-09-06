{{-- Empty state: never bare "No data". --}}
@props(['title' => 'Belum ada data', 'body' => null, 'icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'py-10 px-6 text-center']) }}>
    <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 dark:bg-navy-700 flex items-center justify-center text-slate-400">
        <x-ui.icon :name="$icon" class="w-6 h-6" />
    </div>
    <div class="mt-3 font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</div>
    @if ($body)<p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">{{ $body }}</p>@endif
    @if (trim($action ?? '') !== '')<div class="mt-4">{{ $action }}</div>@endif
</div>
