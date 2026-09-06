<?php

namespace App\Models;

class EquipmentMeterLog extends BaseModel
{
    protected $table = 'equipment_meter_logs';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'hm_start' => 'decimal:1',
            'hm_end' => 'decimal:1',
            'km_start' => 'decimal:1',
            'km_end' => 'decimal:1',
            'operating_hours' => 'decimal:2',
            'idle_hours' => 'decimal:2',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function operator() { return $this->belongsTo(Employee::class, 'operator_id'); }
}
