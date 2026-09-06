<?php

namespace App\Models;

class FuelTransfer extends BaseModel
{
    protected $table = 'fuel_transfers';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['transfer_date' => 'date', 'liter' => 'decimal:3'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function fromTank() { return $this->belongsTo(FuelTank::class, 'from_tank_id'); }
    public function toTank() { return $this->belongsTo(FuelTank::class, 'to_tank_id'); }
}
