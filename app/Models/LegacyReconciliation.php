<?php

namespace App\Models;

class LegacyReconciliation extends BaseModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['legacy_total' => 'decimal:2', 'erp_total' => 'decimal:2', 'variance' => 'decimal:2'];
    }
}
