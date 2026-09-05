<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Rfq extends BaseModel
{
    protected $table = 'rfqs';
    protected $guarded = ['id'];

    public function suppliers() { return $this->hasMany(RfqSupplier::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
