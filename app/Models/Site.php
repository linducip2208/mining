<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Site extends BaseModel
{
    protected $table = 'sites';
    protected $guarded = ['id'];

    public function pits() { return $this->hasMany(Pit::class); }
    public function warehouses() { return $this->hasMany(Warehouse::class); }
    public function crushers() { return $this->hasMany(Crusher::class); }
    public function weighbridges() { return $this->hasMany(Weighbridge::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
}
