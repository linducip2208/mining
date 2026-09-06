<?php

namespace App\Models;

class Budget extends BaseModel
{
    protected $table = 'budgets';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['year' => 'integer'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class, 'cost_center_id'); }
    public function lines() { return $this->hasMany(BudgetLine::class); }
    public function commitments() { return $this->hasMany(BudgetCommitment::class); }
}
