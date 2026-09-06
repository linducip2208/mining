<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class FiscalPeriod extends BaseModel
{
    protected $table = 'fiscal_periods';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function closer() { return $this->belongsTo(User::class, 'closed_by'); }

}
