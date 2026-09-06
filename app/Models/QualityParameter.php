<?php

namespace App\Models;

class QualityParameter extends BaseModel
{
    protected $table = 'quality_parameters';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
