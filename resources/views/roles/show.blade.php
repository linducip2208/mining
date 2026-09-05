@extends('layouts.app')
@section('title', ' - Matriks Izin')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Matriks Izin: {{ $role->name }}</h1>
        <p class="text-sm text-slate-500">{{ $role->permissions->count() }} izin aktif dari {{ $permissions->flatten()->count() }} total</p>
    </div>
    <a href="{{ route('role.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm">Kembali</a>
</div>

<form method="POST" action="{{ route('role.update-permissions', $role) }}">
    @csrf
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr class="text-[11px] uppercase text-slate-500 border-b border-slate-200">
                <th class="px-4 py-2.5 text-left sticky left-0 bg-slate-50">Modul</th>
                <th class="px-2 py-2.5">View</th><th class="px-2 py-2.5">Create</th><th class="px-2 py-2.5">Update</th>
                <th class="px-2 py-2.5">Delete</th><th class="px-2 py-2.5">Approve</th><th class="px-2 py-2.5">Reject</th>
                <th class="px-2 py-2.5">Post</th><th class="px-2 py-2.5">Print</th><th class="px-2 py-2.5">Export</th><th class="px-2 py-2.5">Void</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($permissions as $module => $perms)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-medium sticky left-0 bg-white">{{ $module }}</td>
                    @foreach (['view', 'create', 'update', 'delete', 'approve', 'reject', 'post', 'print', 'export', 'void'] as $group)
                        @php $perm = $perms->firstWhere('group', $group); @endphp
                        <td class="px-2 py-2 text-center">
                            @if ($perm)
                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" @checked($role->permissions->contains('id', $perm->id)) class="rounded text-amber-500">
                            @else
                            <span class="text-slate-200">–</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @can('role.update')
    <div class="mt-4 flex items-center gap-3">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan Matriks</button>
        <label class="text-xs text-slate-500 flex items-center gap-1"><input type="checkbox" class="rounded text-amber-500" onchange="document.querySelectorAll('input[name=\'permissions[]\']').forEach(c => c.checked = this.checked)"> Pilih semua</label>
    </div>
    @endcan
</form>
@endsection