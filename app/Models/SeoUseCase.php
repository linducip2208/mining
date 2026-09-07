<?php

namespace App\Models;

class SeoUseCase extends BaseModel
{
    protected $table = 'seo_use_cases';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['feature_slugs' => 'array']);
    }
}
