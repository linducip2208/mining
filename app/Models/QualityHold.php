<?php

namespace App\Models;

class QualityHold extends BaseModel
{
    protected $table = 'quality_holds';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function sample() { return $this->belongsTo(QcSample::class, 'qc_sample_id'); }
}
