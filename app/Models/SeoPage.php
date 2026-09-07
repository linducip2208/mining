<?php

namespace App\Models;

class SeoPage extends BaseModel
{
    protected $table = 'seo_pages';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'content' => 'array',
            'indexable' => 'boolean',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'last_quality_check_at' => 'datetime',
        ]);
    }

    public const STATUSES = ['DRAFT', 'REVIEW', 'PUBLISHED', 'NOINDEX', 'ARCHIVED'];

    public const INTENTS = ['BUY', 'PRICE', 'SOURCE_CODE', 'SOFTWARE', 'APPLICATION', 'FEATURE', 'INDUSTRY', 'LOCATION', 'COMPARISON', 'CUSTOM', 'INTEGRATION'];

    public function industry()
    {
        return $this->belongsTo(SeoIndustry::class, 'industry_id');
    }

    public function location()
    {
        return $this->belongsTo(SeoLocation::class, 'location_id');
    }

    public function feature()
    {
        return $this->belongsTo(SeoFeature::class, 'feature_id');
    }

    public function useCase()
    {
        return $this->belongsTo(SeoUseCase::class, 'use_case_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED');
    }

    public function scopeIndexable($query)
    {
        return $query->where('status', 'PUBLISHED')->where('indexable', true);
    }

    public function url(): string
    {
        return url('/'.$this->path);
    }

    public function isNoindex(): bool
    {
        return ! $this->indexable || $this->status !== 'PUBLISHED';
    }
}
