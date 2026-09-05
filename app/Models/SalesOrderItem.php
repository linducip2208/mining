<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SalesOrderItem extends BaseModel
{
    protected $table = 'sales_order_items';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
