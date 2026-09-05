@extends('layouts.app')
@section('title', ' - Karyawan')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Karyawan</h1>
    @can('employee.create')<x-btn-create :href="route('employees.create')" />@endcan
</div>
<x-filter-bar :route="route('employees.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nama / NIK..." />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="company_id" label="Perusahaan" type="select" :options="$companies" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">NIP</th><th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Posisi</th>
        <th class="px-4 py-2.5">Site</th><th class="px-4 py-2.5">Tipe</th><th class="px-4 py-2.5 text-right">Gaji Pokok</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $item->code }}</td>
            <td class="px-4 py-2.5 font-medium">{{ $item->name }}</td>
            <td class="px-4 py-2.5">{{ $item->position }}</td>
            <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->employment_type }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->basic_salary, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @can('employee.update')<a href="{{ route('employees.edit', $item) }}" class="text-indigo-600 text-xs hover:underline">Edit</a>@endcan
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada karyawan</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection