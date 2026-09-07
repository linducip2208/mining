<?php

namespace App\Models;

class SeoCtaClick extends BaseModel
{
    protected $table = 'seo_cta_clicks';

    protected $guarded = ['id'];

    public function page()
    {
        return $this->belongsTo(SeoPage::class, 'seo_page_id');
    }
}
