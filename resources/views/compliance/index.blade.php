@extends('layouts.app')

@section('title', ' - Compliance')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Compliance Register</h1>
        <p class="text-sm text-slate-500">Izin, lisensi & sertifikasi — pengingat otomatis H-90/60/30/14/7 · <a href="{{ route('compliance.calendar') }}" class="text-indigo-600 hover:underline">Kalender →</a></p>
    </div>
    @can('compliance.update')
    <x-btn-create label="Tambah Register" :href="route('compliance.create')" />
    @endcan
</div>

<x-filter-bar :route="route('compliance.index')">
    <x-filter-input name="q" label="Cari" placeholder="Judul / nomor dokumen..." />
    <x-filter-input name="type" label="Tipe" type="select" :options="['PERMIT' => 'Izin', 'LICENSE' => 'Lisensi', 'EMP_CERT' => 'Sertifikasi Karyawan', 'EQUIP_CERT' => 'Sertifikasi Alat', 'ENVIRONMENT' => 'Lingkungan', 'CONTRACT' => 'Kontrak', 'OTHER' => 'Lainnya']" />
    <x-filter-input name="status" label="Status" type="select" :options="['ACTIVE' => 'Aktif', 'EXPIRING_SOON' => 'Segera Habis', 'EXPIRED' => 'Kedaluwarsa', 'RENEWED' => 'Diperpanjang', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Judul</th>
        <th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5">Kedaluwarsa</th>
        <th class="px-4 py-2.5">Penanggung Jawab</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5 font-mono">{{ $item->number }}</td>
        <td class="px-4 py-2.5"><a href="{{ route('compliance.show', $item) }}" class="text-indigo-600 hover:underline">{{ $item->title }}</a></td>
        <td class="px-4 py-2.5">{{ $types[$item->type] ?? $item->type }}</td>
        <td class="px-4 py-2.5 {{ $item->status === 'EXPIRED' ? 'text-red-600 font-semibold' : '' }}">{{ $item->expiry_date ?? '—' }}</td>
        <td class="px-4 py-2.5">{{ $item->responsible?->name ?? '—' }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
