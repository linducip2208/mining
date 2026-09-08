<?php

namespace App\Models;

class ImportRowFingerprint extends BaseModel
{
    protected $table = 'import_row_fingerprints';

    protected $guarded = ['id'];

    public function batch()
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
