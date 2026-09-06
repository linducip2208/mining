<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PurchaseOrderItem extends BaseModel
{
    protected $table = 'purchase_order_items';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }

}
