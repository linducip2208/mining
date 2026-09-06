<?php

namespace App\Models;

class BudgetCommitment extends BaseModel
{
    protected $table = 'budget_commitments';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function budget() { return $this->belongsTo(Budget::class); }
    public function line() { return $this->belongsTo(BudgetLine::class, 'budget_line_id'); }
}
