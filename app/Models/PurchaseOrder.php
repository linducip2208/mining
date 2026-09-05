<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PurchaseOrder extends BaseModel
{
    protected $table = 'purchase_orders';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function site() { return $this->belongsTo(Site::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function company() { return $this->belongsTo(Company::class); }

}
