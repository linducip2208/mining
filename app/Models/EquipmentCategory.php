<?php

namespace App\Models;

class EquipmentCategory extends BaseModel
{
    protected $table = 'equipment_categories';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'standard_fuel_lph' => 'decimal:2',
            'fuel_warning_pct' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function equipment() { return $this->hasMany(Equipment::class); }
}
