<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class LegacyImportMatch extends BaseModel
{
    protected $guarded = ['id'];

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
