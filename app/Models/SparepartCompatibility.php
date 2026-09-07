<?php

namespace App\Models;

class SparepartCompatibility extends BaseModel
{
    protected $table = 'sparepart_compatibilities';

    protected $guarded = ['id'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
