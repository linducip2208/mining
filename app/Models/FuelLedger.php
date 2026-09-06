<?php

namespace App\Models;

class FuelLedger extends BaseModel
{
    protected $table = 'fuel_ledger';
    protected $guarded = ['id'];
    public $timestamps = false;
    public $updated_at = false;

    protected function casts(): array
    {
        return [
            'trx_date' => 'date',
            'qty_in' => 'decimal:3',
            'qty_out' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function tank() { return $this->belongsTo(FuelTank::class, 'fuel_tank_id'); }
}
