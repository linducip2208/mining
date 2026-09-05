<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class TechnicianAssignment extends BaseModel
{
    protected $table = 'technician_assignments';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
