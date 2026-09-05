<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CostCenter extends BaseModel
{
    protected $table = 'cost_centers';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
}
