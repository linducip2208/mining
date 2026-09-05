<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
            'force_password_reset' => 'boolean',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('company_id', 'site_id', 'scope');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function hasRole(string $code): bool
    {
        return $this->roles->contains('code', $code);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SUPER_ADMIN');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role', 'permission_id', 'role_id')
            ->distinct();
    }

    public function allPermissions()
    {
        return Permission::whereHas('roles', fn ($q) => $q->whereIn('roles.id', $this->roles->pluck('id')))->get();
    }

    public function hasPermission(string $code): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->roles->load('permissions')->flatMap->permissions->contains('code', $code);
    }

    public function roleScopes()
    {
        return $this->roles->map(fn ($r) => [
            'role' => $r,
            'scope' => $r->pivot->scope,
            'company_id' => $r->pivot->company_id,
            'site_id' => $r->pivot->site_id,
        ]);
    }

    public function accessibleCompanyIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }
        $ids = [];
        foreach ($this->roleScopes() as $s) {
            if ($s['scope'] === 'ALL_COMPANIES') {
                return null;
            }
            if ($s['company_id']) {
                $ids[] = $s['company_id'];
            }
        }
        return array_unique($ids);
    }

    public function accessibleSiteIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }
        foreach ($this->roleScopes() as $s) {
            if ($s['scope'] === 'ALL_COMPANIES' || $s['scope'] === 'COMPANY') {
                return null;
            }
        }
        $ids = [];
        foreach ($this->roleScopes() as $s) {
            if ($s['site_id']) {
                $ids[] = $s['site_id'];
            }
        }
        return $ids ?: null;
    }

    public function canSeeSite($siteId): bool
    {
        if ($siteId === null) {
            return true;
        }
        $sites = $this->accessibleSiteIds();
        return $sites === null || in_array($siteId, $sites);
    }
}
