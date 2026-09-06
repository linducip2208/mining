<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Role extends BaseModel
{
    protected $table = 'roles';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
public function permissions(){return $this->belongsToMany(Permission::class);} public function users(){return $this->belongsToMany(User::class)->withPivot("company_id","branch_id","division_id","department_id","site_id","scope");}
}
