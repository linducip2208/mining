<?php

namespace App\Models;

class MaintenanceSchedule extends BaseModel
{
    protected $table = 'maintenance_schedules';

    protected $guarded = ['id'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return ['last_done' => 'date', 'next_due' => 'date'];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
