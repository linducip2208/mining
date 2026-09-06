<?php

namespace App\Models;

class WeighbridgeDevice extends BaseModel
{
    protected $table = 'weighbridge_devices';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean', 'last_seen_at' => 'datetime'];
    }

    protected $hidden = ['api_token'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function weighbridge() { return $this->belongsTo(Weighbridge::class); }
    public function readings() { return $this->hasMany(WeighbridgeReading::class); }
}
