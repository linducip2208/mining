<?php

namespace App\Models;

class FuelIssue extends BaseModel
{
    protected $table = 'fuel_issues';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'hm_before' => 'decimal:1',
            'hm_after' => 'decimal:1',
            'liter' => 'decimal:3',
            'fuel_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'operating_hours' => 'decimal:2',
            'liter_per_hour' => 'decimal:3',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function tank() { return $this->belongsTo(FuelTank::class, 'fuel_tank_id'); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function operator() { return $this->belongsTo(Employee::class, 'operator_id'); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }
}
