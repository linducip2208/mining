<?php

namespace App\Models;

class LegacyImportSheet extends BaseModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_summary' => 'boolean', 'layout' => 'array'];
    }

    public function batch()
    {
        return $this->belongsTo(LegacyImportBatch::class, 'batch_id');
    }

    public function rows()
    {
        return $this->hasMany(LegacyImportRow::class, 'sheet_id');
    }
}
