<?php

namespace App\Models;

class SeoIndustry extends BaseModel
{
    protected $table = 'seo_industries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['workflow' => 'array']);
    }
}
