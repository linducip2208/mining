<?php

namespace App\Models;

class SeoKeyword extends BaseModel
{
    protected $table = 'seo_keywords';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['indexable' => 'boolean']);
    }
}
