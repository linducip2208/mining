<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Unit extends BaseModel
{
    protected $table = 'units';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
