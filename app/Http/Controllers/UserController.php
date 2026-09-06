<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->when($request->q, function ($q) use ($request) {
            $q->where(fn ($b) => $b->where('name', 'like', "%{$request->q}%")
                ->orWhere('username', 'like', "%{$request->q}%")
                ->orWhere('email', 'like', "%{$request->q}%"));
        })
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('code', $request->role)))
            ->orderBy('id')->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        if (! auth()->user()->hasPermission('user.create')) {
            abort(403);
        }

        return view('users.form', ['roles' => Role::all(), 'user' => null]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('user.create') && ! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|max:150',
            'username' => 'required|max:100|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|max:30',
            'password' => PasswordPolicy::rules(),
            'status' => 'required|in:ACTIVE,INACTIVE,SUSPENDED',
            'roles' => 'array',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $validated['password_changed_at'] = now();
        $validated['created_by'] = auth()->id();
        unset($validated['roles']);

        $user = User::create($validated);
        $this->syncRolesGuarded($user, $request->input('roles', []));
        AuditService::created('USER', $user);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function edit(User $user)
    {
        if (! auth()->user()->hasPermission('user.update')) {
            abort(403);
        }

        return view('users.form', ['user' => $user, 'roles' => Role::all()]);
    }

    public function update(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('user.update')) {
            abort(403);
        }
        $old = $user->toArray();
        $validated = $request->validate([
            'name' => 'required|max:150',
            'username' => 'required|max:100|unique:users,username,'.$user->id,
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|max:30',
            'status' => 'required|in:ACTIVE,INACTIVE,SUSPENDED,LOCKED',
            'roles' => 'array',
        ]);
        unset($validated['roles']);
        $this->guardSelfEdit($user, $validated, $request->input('roles', []));
        $validated['updated_by'] = auth()->id();
        $user->update($validated);
        $this->syncRolesGuarded($user, $request->input('roles', []));
        AuditService::updated('USER', $user, $old);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if (! auth()->user()->hasPermission('user.delete')) {
            abort(403);
        }
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin tidak dapat dihapus.');
        }
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }
        AuditService::deleted('USER', $user);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggle(User $user)
    {
        $this->guardNotSelf($user, 'menonaktifkan akun sendiri');
        $activating = $user->status !== 'ACTIVE';
        $this->requireAnyUserPerm($activating ? ['user.activate', 'user.update'] : ['user.deactivate', 'user.suspend', 'user.update']);
        $user->status = $activating ? 'ACTIVE' : 'INACTIVE';
        $user->save();
        AuditService::log($user->status === 'ACTIVE' ? 'UPDATE' : 'UPDATE', 'USER', $user->id, User::class, null, ['status' => $user->status]);

        return back()->with('success', 'Status pengguna diperbarui: '.$user->status);
    }

    public function unlock(User $user)
    {
        $this->requireAnyUserPerm(['user.unlock', 'user.update']);
        $this->guardNotSelf($user, 'membuka kunci akun sendiri');
        $user->update(['status' => 'ACTIVE', 'failed_login_count' => 0, 'locked_until' => null]);
        AuditService::log('UPDATE', 'USER', $user->id, User::class, null, ['unlocked' => true]);

        return back()->with('success', 'Akun dibuka kunci.');
    }

    public function resetPassword(User $user)
    {
        $this->requireAnyUserPerm(['user.reset_password', 'user.update']);
        $this->guardNotSelf($user, 'mereset password sendiri (gunakan menu Ganti Password)');
        $temp = 'Temp!'.substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 8);
        $user->update([
            'password' => Hash::make($temp),
            'force_password_reset' => true,
            'password_changed_at' => now(),
        ]);
        AuditService::log('UPDATE', 'USER', $user->id, User::class, null, ['password_reset' => true]);

        return back()->with('success', "Password direset. Password sementara: {$temp} (wajib diganti saat login).");
    }

    public function loginHistory(User $user)
    {
        $this->requireAnyUserPerm(['user.view_login_history', 'user.view']);
        $history = LoginHistory::where('user_id', $user->id)->latest()->limit(100)->get();

        return view('users.login-history', compact('user', 'history'));
    }

    public function logoutAll(User $user)
    {
        $this->requireAnyUserPerm(['user.logout_session', 'user.update']);
        $user->sessions()->delete();
        AuditService::log('UPDATE', 'USER', $user->id, User::class, null, ['sessions_terminated' => true]);

        return back()->with('success', 'Semua sesi pengguna diakhiri.');
    }

    public function assignRoles(Request $request, User $user)
    {
        $this->requireAnyUserPerm(['user.assign_role', 'role.update']);
        $this->syncRolesGuarded($user, $request->input('roles', []));
        AuditService::log('UPDATE', 'USER', $user->id, User::class, null, ['roles' => $request->input('roles', [])]);

        return back()->with('success', 'Peran pengguna diperbarui.');
    }

    protected function guardNotSelf(User $user, string $action): void
    {
        if ($user->id === auth()->id()) {
            abort(403, 'Anda tidak dapat '.$action.'.');
        }
    }

    protected function guardSelfEdit(User $user, array $validated, array $roles): void
    {
        if ($user->id !== auth()->id()) {
            return;
        }
        if (isset($validated['status']) && $validated['status'] !== 'ACTIVE') {
            abort(403, 'Anda tidak dapat menonaktifkan akun sendiri.');
        }
        $current = $user->roles->pluck('id')->sort()->values()->all();
        $incoming = collect($roles)->map(fn ($r) => (int) $r)->sort()->values()->all();
        if ($current !== $incoming) {
            abort(403, 'Anda tidak dapat mengubah peran akun sendiri.');
        }
    }

    protected function syncRolesGuarded(User $user, array $roles): void
    {
        if (empty($roles)) {
            $user->roles()->sync([]);

            return;
        }
        $this->requireAnyUserPerm(['user.assign_role', 'role.update'], 'Menetapkan peran memerlukan izin user.assign_role / role.update.');
        $user->roles()->sync($roles);
    }

    protected function authorizeAdmin(): void
    {
        if (! auth()->user()->hasPermission('user.update') && ! auth()->user()->isSuperAdmin()) {
            abort(403);
        }
    }

    protected function requireAnyUserPerm(array $codes, string $message = 'Anda tidak memiliki izin untuk aksi ini.'): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }
        foreach ($codes as $code) {
            if ($user->hasPermission($code)) {
                return;
            }
        }
        abort(403, $message);
    }
}
