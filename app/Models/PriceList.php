<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PriceList extends BaseModel
{
    protected $table = 'price_lists';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ];
    }
    public function items() { return $this->hasMany(PriceListItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function site() { return $this->belongsTo(Site::class); }
}
