<?php

namespace App\Models;

class LegacyImportBatch extends BaseModel
{
    protected $guarded = ['id'];

    public const STATUSES = ['UPLOADED', 'SCANNED', 'MAPPED', 'VALIDATED', 'READY', 'IMPORTING', 'IMPORTED', 'RECONCILING', 'RECONCILED', 'PARTIAL', 'FAILED', 'ROLLED_BACK'];

    public const ROW_STATUSES = ['READY', 'WARNING', 'ERROR', 'DUPLICATE', 'SKIPPED', 'IMPORTED', 'RECONCILED'];

    protected function casts(): array
    {
        return [
            'cutoffs' => 'array',
            'totals' => 'array',
            'reconciliation' => 'array',
            'signoffs' => 'array',
            'allow_accounting_posting' => 'boolean',
            'allow_stock_posting' => 'boolean',
            'cutoff_date' => 'date',
        ];
    }

    public function sheets()
    {
        return $this->hasMany(LegacyImportSheet::class, 'batch_id');
    }

    public function issues()
    {
        return $this->hasMany(LegacyImportIssue::class, 'batch_id');
    }

    public function matches()
    {
        return $this->hasMany(LegacyImportMatch::class, 'batch_id');
    }

    public function reconciliations()
    {
        return $this->hasMany(LegacyReconciliation::class, 'batch_id');
    }

    public function profile()
    {
        return $this->belongsTo(BfjImportProfile::class, 'profile_id');
    }
}
