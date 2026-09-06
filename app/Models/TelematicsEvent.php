<?php

namespace App\Models;

class TelematicsEvent extends BaseModel
{
    protected $table = 'telematics_events';
    protected $guarded = ['id'];
    public $timestamps = false;
    public $updated_at = false;

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'created_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'speed_kph' => 'decimal:2',
            'ignition_on' => 'boolean',
            'engine_hour' => 'decimal:1',
            'odometer_km' => 'decimal:1',
            'fuel_percent' => 'decimal:2',
            'idle_minutes' => 'decimal:2',
            'raw' => 'array',
        ];
    }

    public function provider() { return $this->belongsTo(TelematicsProvider::class, 'telematics_provider_id'); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
}
