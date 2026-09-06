<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MaintenanceCost extends BaseModel
{
    protected $table = 'maintenance_costs';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function workOrder() { return $this->belongsTo(WorkOrder::class); }

}
