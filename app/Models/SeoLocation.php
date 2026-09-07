<?php

namespace App\Models;

class SeoLocation extends BaseModel
{
    protected $table = 'seo_locations';

    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(SeoLocation::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SeoLocation::class, 'parent_id');
    }

    public function displayName(): string
    {
        return $this->name;
    }
}
