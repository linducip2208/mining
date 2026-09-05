<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class WorkOrder extends BaseModel
{
    protected $table = 'work_orders';
    protected $guarded = ['id'];

    public function tasks() { return $this->hasMany(WorkOrderTask::class); }
    public function parts() { return $this->hasMany(MaintenancePart::class); }
    public function technicians() { return $this->hasMany(TechnicianAssignment::class); }
    public function costs() { return $this->hasMany(MaintenanceCost::class); }
    public function downtimes() { return $this->hasMany(Downtime::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function site() { return $this->belongsTo(Site::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function company() { return $this->belongsTo(Company::class); }

}
