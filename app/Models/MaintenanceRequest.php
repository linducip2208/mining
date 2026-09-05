<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MaintenanceRequest extends BaseModel
{
    protected $table = 'maintenance_requests';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
