<?php

namespace App\Models;

class HseCorrectiveAction extends BaseModel
{
    protected $table = 'hse_corrective_actions';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'closed_at' => 'datetime'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function report() { return $this->belongsTo(HseReport::class, 'hse_report_id'); }
    public function responsible() { return $this->belongsTo(Employee::class, 'responsible_id'); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'OPEN' && $this->due_date && $this->due_date->isPast();
    }
}
