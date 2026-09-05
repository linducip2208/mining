<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class OperatorIncentive extends BaseModel
{
    protected $table = 'operator_incentives';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function employee() { return $this->belongsTo(Employee::class); }
    public function site() { return $this->belongsTo(Site::class); }

    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}
