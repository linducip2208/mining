<?php

namespace App\Models;

class SeoFeature extends BaseModel
{
    protected $table = 'seo_features';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'workflow' => 'array',
            'implemented' => 'boolean',
            'marketing_enabled' => 'boolean',
        ]);
    }

    public function scopeMarketable($query)
    {
        return $query->where('implemented', true)->where('marketing_enabled', true);
    }
}
