<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SupplierQuotationItem extends BaseModel
{
    protected $table = 'supplier_quotation_items';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
