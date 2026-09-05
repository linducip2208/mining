<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class StockTransfer extends BaseModel
{
    protected $table = 'stock_transfers';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }
    public function items() { return $this->hasMany(StockTransferItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function fromWarehouse() { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse() { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }

}
