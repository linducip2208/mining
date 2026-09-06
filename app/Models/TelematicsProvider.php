<?php

namespace App\Models;

class TelematicsProvider extends BaseModel
{
    protected $table = 'telematics_providers';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean', 'last_sync_at' => 'datetime'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function events() { return $this->hasMany(TelematicsEvent::class); }
}
