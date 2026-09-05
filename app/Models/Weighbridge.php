<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Weighbridge extends BaseModel
{
    protected $table = 'weighbridges';
    protected $guarded = ['id'];

    public function calibrations() { return $this->hasMany(WeighbridgeCalibration::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
