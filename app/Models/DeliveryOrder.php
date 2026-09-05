<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DeliveryOrder extends BaseModel
{
    protected $table = 'delivery_orders';
    protected $guarded = ['id'];

    public function items() { return $this->hasMany(DeliveryOrderItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function ticket() { return $this->belongsTo(WeighbridgeTicket::class, 'weighbridge_ticket_id'); }
    public function vehicle() { return $this->belongsTo(Equipment::class, 'vehicle_id'); }

}
