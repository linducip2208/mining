<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class GoodsReceipt extends BaseModel
{
    protected $table = 'goods_receipts';
    protected $guarded = ['id'];

    public function items() { return $this->hasMany(GoodsReceiptItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creatorUser() { return $this->belongsTo(User::class, 'created_by'); }

}
