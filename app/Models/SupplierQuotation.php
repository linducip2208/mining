<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SupplierQuotation extends BaseModel
{
    protected $table = 'supplier_quotations';
    protected $guarded = ['id'];

    public function items() { return $this->hasMany(Item::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
