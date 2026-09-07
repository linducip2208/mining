@extends('layouts.app')
@section('title', ' - SEO Catalog')
@section('content')
<x-ui.page-header title="SEO Catalog" description="Entitas data-driven pSEO. Klaim fitur hanya dari yang implemented + marketing_enabled.">
    <x-slot:actions>
        <x-ui.button size="sm" variant="secondary" :href="route('marketing.seo.dashboard')">Dashboard</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="flex flex-wrap gap-2 mb-4 text-sm">
    @foreach(['features' => 'Features', 'industries' => 'Industries', 'locations' => 'Locations', 'usecases' => 'Use Cases', 'keywords' => 'Keywords'] as $key => $label)
    <a href="{{ route('marketing.seo.catalog', ['tab' => $key]) }}" class="px-4 py-2 rounded-lg min-h-[38px] inline-flex items-center {{ $tab === $key ? 'bg-slate-900 text-white font-semibold' : 'bg-white border border-slate-200' }}">{{ $label }}</a>
    @endforeach
</div>

<x-table>
    <x-slot:head>
        @if($tab === 'features')<th class="px-4 py-2.5">Feature</th><th class="px-4 py-2.5">Modul</th><th class="px-4 py-2.5">Marketing</th><th class="px-4 py-2.5"></th>
        @elseif($tab === 'keywords')<th class="px-4 py-2.5">Keyword</th><th class="px-4 py-2.5">Intent</th><th class="px-4 py-2.5 text-right">Commercial</th>
        @else<th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Slug</th>@endif
    </x-slot:head>
    <tbody>
        @if($tab === 'features')
            @foreach($features as $f)<tr class="hover:bg-slate-50"><td class="px-4 py-2.5 font-medium">{{ $f->name }}</td><td class="px-4 py-2.5 text-xs">{{ $f->related_module }}</td><td class="px-4 py-2.5 text-xs">{{ $f->marketing_enabled ? 'Aktif' : 'Nonaktif' }}</td><td class="px-4 py-2.5 text-right"><form action="{{ route('marketing.seo.feature.toggle', $f) }}" method="POST" class="inline">@csrf<button class="text-xs text-amber-600 hover:underline">Toggle</button></form></td></tr>@endforeach
        @elseif($tab === 'industries')
            @foreach($industries as $i)<tr class="hover:bg-slate-50"><td class="px-4 py-2.5 font-medium">{{ $i->name }}</td><td class="px-4 py-2.5 text-xs font-mono">{{ $i->slug }}</td></tr>@endforeach
        @elseif($tab === 'locations')
            @foreach($locations as $l)<tr class="hover:bg-slate-50"><td class="px-4 py-2.5 font-medium">{{ $l->name }} <span class="text-xs text-slate-400">({{ $l->type }})</span></td><td class="px-4 py-2.5 text-xs font-mono">{{ $l->slug }}</td></tr>@endforeach
        @elseif($tab === 'usecases')
            @foreach($usecases as $u)<tr class="hover:bg-slate-50"><td class="px-4 py-2.5 font-medium">{{ $u->name }}</td><td class="px-4 py-2.5 text-xs font-mono">{{ $u->slug }}</td></tr>@endforeach
        @else
            @foreach($keywords as $k)<tr class="hover:bg-slate-50"><td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $k->keyword }}</td><td class="px-4 py-2.5 text-xs">{{ $k->intent }}</td><td class="px-4 py-2.5 text-right">{{ $k->commercial_score }}</td></tr>@endforeach
        @endif
    </tbody>
    <x-slot:footer>{{ ($features ?? $industries ?? $locations ?? $usecases ?? $keywords)->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
