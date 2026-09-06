<?php

namespace App\Models;

class Tire extends BaseModel
{
    protected $table = 'tires';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'purchase_cost' => 'decimal:2',
            'purchase_date' => 'date',
            'install_date' => 'date',
            'install_hm' => 'decimal:1',
            'install_km' => 'decimal:1',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function movements() { return $this->hasMany(TireMovement::class); }

    /** Lifetime HM since install (uses current equipment reading). */
    public function lifetimeHm(): float
    {
        $current = (float) ($this->equipment?->meter_reading ?? $this->install_hm);
        return max(round($current - (float) $this->install_hm, 1), 0);
    }

    public function costPerHour(): float
    {
        $life = $this->lifetimeHm();
        $repairs = (float) $this->movements()->sum('cost');
        return $life > 0 ? round(((float) $this->purchase_cost + $repairs) / $life, 2) : 0;
    }
}
