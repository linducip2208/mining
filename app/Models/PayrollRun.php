<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PayrollRun extends BaseModel
{
    protected $table = 'payroll_runs';
    protected $guarded = ['id'];

    public function details() { return $this->hasMany(PayrollDetail::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
}
