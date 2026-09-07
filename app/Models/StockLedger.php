<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class StockLedger extends BaseModel
{
    protected $table = 'stock_ledger';
    protected $guarded = ['id'];
    public $timestamps = false;
    public $updated_at = false;

    protected function casts(): array
    {
        return [
            'trx_date' => 'date',
            'qty_in' => 'decimal:4',
            'qty_out' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }

}
