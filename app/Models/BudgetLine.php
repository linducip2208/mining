<?php

namespace App\Models;

class BudgetLine extends BaseModel
{
    protected $table = 'budget_lines';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function budget() { return $this->belongsTo(Budget::class); }
    public function chartOfAccount() { return $this->belongsTo(ChartOfAccount::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class, 'cost_center_id'); }
}
