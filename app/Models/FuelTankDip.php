<?php

namespace App\Models;

class FuelTankDip extends BaseModel
{
    protected $table = 'fuel_tank_dips';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'dip_date' => 'date',
            'dip_cm' => 'decimal:2',
            'physical_liter' => 'decimal:3',
            'system_liter' => 'decimal:3',
            'variance' => 'decimal:3',
        ];
    }

    public function tank() { return $this->belongsTo(FuelTank::class, 'fuel_tank_id'); }
}
