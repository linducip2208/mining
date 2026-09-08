<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class BfjMasterAlias extends BaseModel
{
    protected $guarded = ['id'];

    public const STATUSES = ['EXACT_MATCH', 'ALIAS_MATCH', 'POSSIBLE_MATCH', 'NEW_MASTER_REQUIRED', 'RESOLVED', 'IGNORED'];

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
