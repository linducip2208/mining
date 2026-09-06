<?php

namespace App\Models;

class EquipmentInspection extends BaseModel
{
    protected $table = 'equipment_inspections';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'checklist' => 'array',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function inspector() { return $this->belongsTo(Employee::class, 'inspector_id'); }
}
