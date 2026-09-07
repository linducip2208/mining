<?php

namespace App\Models;

class ImportBatch extends BaseModel
{
    protected $table = 'import_batches';

    protected $guarded = ['id'];

    public const TYPES = ['sparepart_master', 'opening_stock', 'letter_register', 'legacy_invoice', 'legacy_receipt'];

    public const STATUSES = ['UPLOADED', 'MAPPED', 'VALIDATED', 'IMPORTED', 'FAILED'];

    protected function casts(): array
    {
        return ['column_map' => 'array', 'preview' => 'array', 'post_accounting' => 'boolean'];
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
