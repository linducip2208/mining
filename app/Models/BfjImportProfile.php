<?php

namespace App\Models;

class BfjImportProfile extends BaseModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean'];
    }

    public function aliases()
    {
        return $this->hasMany(BfjColumnAlias::class, 'profile_id');
    }

    public static function bfj(): self
    {
        return static::firstOrCreate(['code' => 'BFJ_LEGACY_2026'], [
            'name' => 'BFJ Legacy 2026 Migration Profile',
            'config' => [],
            'is_active' => true,
        ]);
    }

    public function cfg(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
