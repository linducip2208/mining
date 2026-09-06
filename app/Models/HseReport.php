<?php

namespace App\Models;

class HseReport extends BaseModel
{
    protected $table = 'hse_reports';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function correctiveActions() { return $this->hasMany(HseCorrectiveAction::class); }
}
