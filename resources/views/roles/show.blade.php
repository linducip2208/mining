@extends('layouts.app')
@section('title', ' - Matriks Izin')
@section('content')
<x-ui.page-header title="Matriks Izin: {{ $role->name }}" description="{{ $role->permissions->count() }} izin aktif dari {{ $permissions->flatten()->count() }} total.">
    <x-slot:actions>
        <x-ui.button variant="secondary" size="sm" :href="route('role.index')">Kembali</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('role.update-permissions', $role) }}">
    @csrf
    <div class="mb-3 max-w-sm" x-data>
        <input type="search" id="permSearch" placeholder="Cari modul…" aria-label="Cari modul izin"
            class="w-full px-3 py-2 rounded-ctl border border-slate-200 dark:border-slate-700 bg-white dark:bg-navy-800 text-sm outline-none focus:border-amber-400">
    </div>
    <x-ui.table :sticky="true">
        <x-slot:head>
            <th class="px-4 py-2.5 text-left sticky left-0 bg-slate-50 dark:bg-navy-900/80">Modul</th>
            @foreach (['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus', 'approve' => 'Setujui', 'reject' => 'Tolak', 'post' => 'Posting', 'print' => 'Cetak', 'export' => 'Ekspor', 'void' => 'Void'] as $g => $label)
            <th class="px-2 py-2.5 text-center" title="{{ $label }}">{{ $label }}<br>
                <input type="checkbox" aria-label="Pilih semua {{ $label }}" class="rounded text-amber-500 mt-1 perm-col-toggle" data-group="{{ $g }}">
            </th>
            @endforeach
        </x-slot:head>
        @foreach ($permissions as $module => $perms)
        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 perm-row" data-module="{{ strtolower($module) }}">
            <td class="px-4 py-2 font-medium sticky left-0 bg-white dark:bg-navy-800">{{ $module }}</td>
            @foreach (['view', 'create', 'update', 'delete', 'approve', 'reject', 'post', 'print', 'export', 'void'] as $group)
                @php $perm = $perms->firstWhere('group', $group); @endphp
                <td class="px-2 py-2 text-center">
                    @if ($perm)
                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" @checked($role->permissions->contains('id', $perm->id)) class="rounded text-amber-500 perm-cb" data-group="{{ $group }}" aria-label="{{ $module }} {{ $group }}">
                    @else
                    <span class="text-slate-200 dark:text-slate-700" aria-hidden="true">—</span>
                    @endif
                </td>
            @endforeach
        </tr>
        @endforeach
    </x-ui.table>
    @can('role.update')
    <div class="mt-4 flex items-center gap-3">
        <x-ui.button>Simpan Matriks</x-ui.button>
        <label class="text-xs text-slate-500 flex items-center gap-1.5"><input type="checkbox" class="rounded text-amber-500" onchange="document.querySelectorAll('.perm-cb').forEach(c => c.checked = this.checked)"> Pilih semua</label>
    </div>
    @endcan
</form>
<script>
(function () {
    var s = document.getElementById('permSearch');
    if (s) s.addEventListener('input', function () {
        var q = s.value.toLowerCase();
        document.querySelectorAll('.perm-row').forEach(function (r) {
            r.style.display = r.dataset.module.includes(q) ? '' : 'none';
        });
    });
    document.querySelectorAll('.perm-col-toggle').forEach(function (t) {
        t.addEventListener('change', function () {
            document.querySelectorAll('.perm-cb[data-group="' + t.dataset.group + '"]').forEach(function (c) {
                if (c.closest('tr').style.display !== 'none') c.checked = t.checked;
            });
        });
    });
})();
</script>
@endsection
