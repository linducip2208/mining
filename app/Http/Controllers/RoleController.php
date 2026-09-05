<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('code')->get();
        return view('roles.index', compact('roles'));
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('module')->orderBy('group')->get()->groupBy('module');
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('sort')->get();
        return view('roles.show', compact('role', 'permissions', 'menus'));
    }

    public function create()
    {
        return view('roles.form', ['role' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|max:50|unique:roles,code',
            'name' => 'required|max:100',
            'description' => 'nullable',
        ]);
        $role = Role::create($validated);
        AuditService::created('ROLE', $role);
        return redirect()->route('role.index')->with('success', 'Peran berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        return view('roles.form', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'code' => 'required|max:50|unique:roles,code,' . $role->id,
            'name' => 'required|max:100',
            'description' => 'nullable',
        ]);
        $role->update($validated);
        AuditService::updated('ROLE', $role);
        return redirect()->route('role.index')->with('success', 'Peran berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'Peran sistem tidak dapat dihapus.');
        }
        AuditService::deleted('ROLE', $role);
        $role->delete();
        return redirect()->route('role.index')->with('success', 'Peran berhasil dihapus.');
    }

    /**
     * Permission matrix save.
     */
    public function updatePermissions(Request $request, Role $role)
    {
        $permissionIds = $request->input('permissions', []);
        $role->permissions()->sync($permissionIds);
        AuditService::log('UPDATE', 'ROLE', $role->id, Role::class, null, ['permission_count' => count($permissionIds)]);
        return back()->with('success', 'Matriks izin untuk ' . $role->name . ' diperbarui (' . count($permissionIds) . ' izin).');
    }
}
