<?php

namespace App\Models;

class WeighbridgeReading extends BaseModel
{
    protected $table = 'weighbridge_readings';
    protected $guarded = ['id'];
    public $timestamps = false;
    public $updated_at = false;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'raw_weight' => 'decimal:4',
            'stable_weight' => 'decimal:4',
            'is_stable' => 'boolean',
            'is_manual' => 'boolean',
        ];
    }

    public function device() { return $this->belongsTo(WeighbridgeDevice::class, 'weighbridge_device_id'); }
    public function weighbridge() { return $this->belongsTo(Weighbridge::class); }
    public function ticket() { return $this->belongsTo(WeighbridgeTicket::class, 'weighbridge_ticket_id'); }
}
