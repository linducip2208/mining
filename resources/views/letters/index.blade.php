@extends('layouts.app')
@section('title', ' - Register Surat')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Register Surat</h1>
        <p class="text-sm text-slate-500">Administrasi nomor dan dokumen surat</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('letter.view')<a href="{{ route('letter-types.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm min-h-[38px] inline-flex items-center">Jenis Surat</a>@endcan
        @can('letter.create')<x-btn-create label="Surat Baru" :href="route('letters.create')" />@endcan
    </div>
</div>

<x-filter-bar :route="route('letters.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor / perihal / tujuan..." />
    <x-filter-input name="type_id" label="Jenis" type="select" :options="$types->pluck('name', 'id')->all()" />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="month" label="Bulan" type="select" :options="['1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr', '5' => 'Mei', '6' => 'Jun', '7' => 'Jul', '8' => 'Agu', '9' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des']" />
    <x-filter-input name="year" label="Tahun" placeholder="2026" />
</x-filter-bar>

@if($letter)
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs text-slate-400">{{ $letter->type?->name }} · {{ $letter->letter_date?->format('d/m/Y') }}</div>
            <h2 class="text-lg font-bold break-words">{{ $letter->number ?? '(tanpa nomor)' }}</h2>
            <p class="font-medium mt-1">{{ $letter->subject }}</p>
            <p class="text-sm text-slate-500 mt-1">Kepada: {{ $letter->recipient_name }} {{ $letter->recipient_company ? '(' . $letter->recipient_company . ')' : '' }}</p>
            @if($letter->related)<p class="text-xs text-slate-400 mt-1">Terkait: {{ class_basename($letter->related_type) }} #{{ $letter->related_id }}</p>@endif
        </div>
        <x-status-badge :status="$letter->status" />
    </div>
    @if($letter->body)<div class="mt-3 text-sm whitespace-pre-line">{{ $letter->body }}</div>@endif
    <div class="flex flex-wrap gap-2 mt-4">
        @if($letter->status === 'DRAFT')
            @can('letter.create')<form action="{{ route('letters.reserve', $letter) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold min-h-[38px]">Reserve Nomor</button></form>@endcan
            @can('letter.approve')<form action="{{ route('letters.publish', $letter) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg border border-slate-200 text-sm min-h-[38px]">Publish (sederhana)</button></form>@endcan
        @endif
        @if(in_array($letter->status, ['DRAFT', 'NUMBER_RESERVED']))
            @can('letter.submit')<form action="{{ route('letters.review', $letter) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[38px]">Ajukan Review</button></form>@endcan
        @endif
        @if($letter->status === 'REVIEW')
            @can('letter.approve')<form action="{{ route('letters.approve', $letter) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold min-h-[38px]">Setujui</button></form>@endcan
        @endif
        @if($letter->status === 'APPROVED')
            @can('letter.approve')<form action="{{ route('letters.sign', $letter) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold min-h-[38px]">Tandatangani</button></form>@endcan
        @endif
        @if(in_array($letter->status, ['SIGNED', 'APPROVED', 'PUBLISHED']))
            @can('letter.send')<form action="{{ route('letters.send', $letter) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-semibold min-h-[38px]">Kirim</button></form>@endcan
        @endif
        @if(in_array($letter->status, ['SENT', 'SIGNED', 'PUBLISHED']))
            @can('letter.archive')<form action="{{ route('letters.archive', $letter) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg border border-slate-200 text-sm min-h-[38px]">Arsipkan</button></form>@endcan
        @endif
        @can('letter.update')
        <form action="{{ route('letters.attach', $letter) }}" method="POST" enctype="multipart/form-data" class="flex gap-2 items-center">@csrf<input type="file" name="attachment" accept=".pdf,.docx,.xlsx,.png,.jpg,.jpeg" class="text-xs max-w-[180px]"><button class="px-3 py-2 rounded-lg border border-slate-200 text-xs min-h-[38px]">Upload</button></form>
        @endcan
        <a href="{{ route('letters.print', $letter) }}" target="_blank" class="px-4 py-2 rounded-lg border border-slate-200 text-sm min-h-[38px] inline-flex items-center">Cetak</a>
        @if($letter->attachment_path)<a href="{{ route('letters.download', $letter) }}" class="px-4 py-2 rounded-lg border border-slate-200 text-sm min-h-[38px] inline-flex items-center">Unduh ({{ $letter->attachment_mime }})</a>@endif
        @if(!in_array($letter->status, ['VOID', 'ARCHIVED']))
            @can('letter.void')<form action="{{ route('letters.void', $letter) }}" method="POST" onsubmit="return confirm('Void surat ini? Nomor tidak dipakai ulang.')">@csrf<input type="hidden" name="reason" value="Void dari register"><button class="px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm min-h-[38px]">Void</button></form>@endcan
        @endif
    </div>
</div>
@endif

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Jenis</th>
        <th class="px-4 py-2.5">Perihal</th><th class="px-4 py-2.5">Tujuan</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $item->number ?? '-' }}</td>
            <td class="px-4 py-2.5 whitespace-nowrap text-xs">{{ $item->letter_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $item->type?->code }}</td>
            <td class="px-4 py-2.5">{{ $item->subject }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->recipient_name }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right"><a href="{{ route('letters.show', $item) }}" class="text-amber-600 hover:underline text-xs">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada surat</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
