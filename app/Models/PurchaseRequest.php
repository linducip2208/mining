<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PurchaseRequest extends BaseModel
{
    protected $table = 'purchase_requests';
    protected $guarded = ['id'];

    public function items() { return $this->hasMany(PurchaseRequestItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }

}
