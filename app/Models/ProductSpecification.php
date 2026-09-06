<?php

namespace App\Models;

class ProductSpecification extends BaseModel
{
    protected $table = 'product_specifications';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'expiry_date' => 'date'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function lines() { return $this->hasMany(ProductSpecLine::class); }
}
