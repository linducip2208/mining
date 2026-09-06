<?php

namespace App\Models;

class StockpileMovement extends BaseModel
{
    protected $table = 'stockpile_movements';
    protected $guarded = ['id'];
    public $timestamps = false;
    public $updated_at = false;

    protected function casts(): array
    {
        return [
            'trx_date' => 'date',
            'qty_in' => 'decimal:4',
            'qty_out' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function stockpile() { return $this->belongsTo(Stockpile::class); }
}
