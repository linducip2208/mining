<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Leave extends BaseModel
{
    protected $table = 'leaves';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function employee() { return $this->belongsTo(Employee::class); }

}
