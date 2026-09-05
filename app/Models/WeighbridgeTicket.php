<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class WeighbridgeTicket extends BaseModel
{
    protected $table = 'weighbridge_tickets';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function weighbridge() { return $this->belongsTo(Weighbridge::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function operator() { return $this->belongsTo(User::class, 'operator_id'); }
    public function company() { return $this->belongsTo(Company::class); }

}
