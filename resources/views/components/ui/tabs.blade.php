{{-- Tabs: tabs = [['id'=>, 'label'=>]]; panels via $slot named? Use simple: content blocks keyed by tab id passed as $panels array of html. Simpler pattern: caller renders tab buttons + divs with x-show. This component renders the tab bar only. --}}
@props(['tabs' => [], 'active' => null])
@php $first = $active ?? ($tabs[0]['id'] ?? 'tab'); @endphp
<div x-data="{ tab: '{{ $first }}' }" {{ $attributes }}>
    <div class="flex gap-1 border-b border-slate-200 dark:border-slate-700/60 overflow-x-auto nice-scroll" role="tablist" aria-label="Tabs">
        @foreach ($tabs as $t)
        <button type="button" role="tab" :aria-selected="tab === '{{ $t['id'] }}'" @click="tab = '{{ $t['id'] }}'"
            :class="tab === '{{ $t['id'] }}' ? 'text-amber-600 dark:text-amber-400 border-amber-500' : 'text-slate-500 dark:text-slate-400 border-transparent hover:text-slate-700'"
            class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap">{{ $t['label'] }}</button>
        @endforeach
    </div>
    <div class="pt-4">{{ $slot }}</div>
</div>
