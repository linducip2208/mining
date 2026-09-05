<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Permission extends BaseModel
{
    protected $table = 'permissions';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
public function roles(){return $this->belongsToMany(Role::class);}
}
