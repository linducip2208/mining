<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MiningProductionDetail extends BaseModel
{
    protected $table = 'mining_production_details';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
