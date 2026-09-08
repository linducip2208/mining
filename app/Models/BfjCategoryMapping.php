<?php

namespace App\Models;

class BfjCategoryMapping extends BaseModel
{
    protected $guarded = ['id'];

    public function coa()
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }
}
