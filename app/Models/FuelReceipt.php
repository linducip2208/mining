<?php

namespace App\Models;

class FuelReceipt extends BaseModel
{
    protected $table = 'fuel_receipts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'liter' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function tank() { return $this->belongsTo(FuelTank::class, 'fuel_tank_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }
}
