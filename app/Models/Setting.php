<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class Setting extends BaseModel
{
    protected $table = 'settings';

    protected $guarded = ['id'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function get($key, $default = null, string $scopeType = 'GLOBAL', ?int $scopeId = null)
    {
        $query = static::where('key', $key);
        if (static::hasScopeColumns()) {
            $query->where('scope_type', $scopeType)->where('scope_id', $scopeId);
        }
        $setting = $query->first();
        if (! $setting) {
            return $default;
        }
        if ($setting->type === 'secret') {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Throwable) {
                return $setting->value;
            }
        }

        return $setting->value;
    }

    public static function set($key, $value, $type = 'string', string $scopeType = 'GLOBAL', ?int $scopeId = null)
    {
        $attributes = ['key' => $key];
        if (static::hasScopeColumns()) {
            $attributes['scope_type'] = $scopeType;
            $attributes['scope_id'] = $scopeId;
        }
        $storedValue = $type === 'secret' && filled($value) ? Crypt::encryptString((string) $value) : $value;

        return static::updateOrCreate($attributes, ['value' => $storedValue, 'type' => $type, 'updated_by' => auth()->id()]);
    }

    private static function hasScopeColumns(): bool
    {
        return Schema::hasColumn('settings', 'scope_type');
    }
}
