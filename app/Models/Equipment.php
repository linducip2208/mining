<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Equipment extends BaseModel
{
    protected $table = 'equipment';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function category() { return $this->belongsTo(EquipmentCategory::class, 'equipment_category_id'); }
    public function operator() { return $this->belongsTo(Employee::class, 'operator_id'); }
    public function meterLogs() { return $this->hasMany(EquipmentMeterLog::class); }
    public function inspections() { return $this->hasMany(EquipmentInspection::class); }
    public function assignments() { return $this->hasMany(EquipmentAssignment::class); }
    public function downtimes() { return $this->hasMany(Downtime::class); }
    public function workOrders() { return $this->hasMany(WorkOrder::class); }
    public function fuelIssues() { return $this->hasMany(FuelIssue::class); }

}
