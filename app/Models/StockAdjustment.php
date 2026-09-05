<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class StockAdjustment extends BaseModel
{
    protected $table = 'stock_adjustments';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }
    public function items() { return $this->hasMany(StockAdjustmentItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function warehouse() { return $this->belongsTo(Warehouse::class); }

}
