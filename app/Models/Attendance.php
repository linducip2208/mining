<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Attendance extends BaseModel
{
    protected $table = 'attendances';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    public function employee() { return $this->belongsTo(Employee::class); }
}
