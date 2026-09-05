<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Company extends BaseModel
{
    protected $table = 'companies';
    protected $guarded = ['id'];

    public function branches() { return $this->hasMany(Branch::class); }
    public function sites() { return $this->hasMany(Site::class); }
    public function divisions() { return $this->hasMany(Division::class); }
    public function users() { return $this->hasMany(User::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
