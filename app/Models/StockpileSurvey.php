<?php

namespace App\Models;

class StockpileSurvey extends BaseModel
{
    protected $table = 'stockpile_surveys';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'survey_date' => 'date',
            'survey_balance' => 'decimal:4',
            'system_balance' => 'decimal:4',
            'variance' => 'decimal:4',
            'variance_pct' => 'decimal:3',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function stockpile() { return $this->belongsTo(Stockpile::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
