<?php

namespace App\Models;

class SupplierContract extends BaseModel
{
    protected $table = 'supplier_contracts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'contract_qty' => 'decimal:4',
            'contract_value' => 'decimal:2',
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function item() { return $this->belongsTo(Item::class); }

    public function realization(): array
    {
        return \App\Services\ContractService::supplierRealization($this);
    }
}
