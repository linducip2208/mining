@extends('layouts.app')
@section('title', ' - SEO Pages')
@section('content')
<x-ui.page-header title="SEO Pages" description="Kelola hasil generate programmatic SEO.">
    <x-slot:actions>
        <x-ui.button size="sm" variant="secondary" :href="route('marketing.seo.dashboard')">Dashboard</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-filter-bar :route="route('marketing.seo.pages')">
    <x-filter-input name="q" label="Cari" placeholder="Keyword / path..." />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="cluster" label="Cluster" type="select" :options="$clusters->combine($clusters)->all()" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Keyword</th><th class="px-4 py-2.5">Path</th><th class="px-4 py-2.5">Intent</th>
        <th class="px-4 py-2.5 text-right">Quality</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $item->keyword }}</td>
            <td class="px-4 py-2.5 text-xs font-mono whitespace-nowrap">/{{ $item->path }}</td>
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $item->intent }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">{{ $item->quality_score }}/{{ $item->uniqueness_score }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap"><a href="{{ route('marketing.seo.show', $item) }}" class="text-amber-600 hover:underline text-xs">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada halaman. Jalankan <code class="font-mono">php artisan seo:generate --tier=1</code>.</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
