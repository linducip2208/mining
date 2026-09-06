<?php

namespace App\Models;

class TireMovement extends BaseModel
{
    protected $table = 'tire_movements';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'trx_date' => 'date',
            'hm_reading' => 'decimal:1',
            'km_reading' => 'decimal:1',
            'tread_depth' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function tire() { return $this->belongsTo(Tire::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
}
