<?php

namespace App\Models;

class ComplianceRegister extends BaseModel
{
    protected $table = 'compliance_registers';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'reminder_days' => 'array',
            'last_reminded_at' => 'date',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function document() { return $this->belongsTo(Document::class); }

    public function daysToExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->expiry_date, false);
    }
}
