<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PriceHistory extends BaseModel
{
    protected $table = 'price_history';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
