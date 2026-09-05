<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MaintenanceSchedule extends BaseModel
{
    protected $table = 'maintenance_schedules';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function asset() { return $this->belongsTo(Asset::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }

}
