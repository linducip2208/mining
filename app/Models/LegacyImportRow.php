<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class LegacyImportRow extends BaseModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['source' => 'array', 'normalized' => 'array'];
    }

    public function sheet()
    {
        return $this->belongsTo(LegacyImportSheet::class, 'sheet_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
