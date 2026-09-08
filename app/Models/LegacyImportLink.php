<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class LegacyImportLink extends BaseModel
{
    protected $guarded = ['id'];

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
