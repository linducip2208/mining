<?php

namespace App\Models;

class FuelTank extends BaseModel
{
    protected $table = 'fuel_tanks';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['capacity_liter' => 'decimal:2', 'status' => 'boolean'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function ledger() { return $this->hasMany(FuelLedger::class); }
}
