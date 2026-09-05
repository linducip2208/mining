<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SalesOrder extends BaseModel
{
    protected $table = 'sales_orders';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function items() { return $this->hasMany(SalesOrderItem::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function deliveryOrders() { return $this->hasMany(DeliveryOrder::class); }
    public function invoice() { return $this->hasOne(Invoice::class); }

}
