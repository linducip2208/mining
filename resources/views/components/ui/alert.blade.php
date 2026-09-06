{{-- Alert: info/success/warning/danger/critical. Critical gets stronger treatment. --}}
@props(['type' => 'info', 'title' => null])
@php
$map = [
    'info' => ['bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/30', 'text-blue-600 dark:text-blue-300', 'bell'],
    'success' => ['bg-green-50 dark:bg-green-500/10 border-green-200 dark:border-green-500/30', 'text-green-600 dark:text-green-300', 'check-circle'],
    'warning' => ['bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30', 'text-amber-600 dark:text-amber-300', 'alert'],
    'danger' => ['bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/30', 'text-red-600 dark:text-red-300', 'alert'],
    'critical' => ['bg-red-100 dark:bg-red-500/15 border-red-300 dark:border-red-500/50 border-l-4', 'text-red-700 dark:text-red-200', 'alert'],
];
[$box, $txt, $icon] = $map[$type] ?? $map['info'];
@endphp
<div {{ $attributes->merge(['class' => "rounded-card border p-4 $box", 'role' => 'alert']) }}>
    <div class="flex gap-3">
        <x-ui.icon :name="$icon" class="w-5 h-5 flex-none {{ $txt }}" />
        <div class="min-w-0">
            @if ($title)<div class="font-semibold text-sm {{ $txt }}">{{ $title }}</div>@endif
            <div class="text-sm text-slate-700 dark:text-slate-200 mt-0.5">{{ $slot }}</div>
        </div>
    </div>
</div>
