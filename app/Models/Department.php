<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Department extends BaseModel
{
    protected $table = 'departments';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function division() { return $this->belongsTo(Division::class); }
}
