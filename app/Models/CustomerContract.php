<?php

namespace App\Models;

class CustomerContract extends BaseModel
{
    protected $table = 'customer_contracts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'contract_qty' => 'decimal:4',
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function site() { return $this->belongsTo(Site::class); }

    /** Live realization (never stored — always computed). */
    public function realization(): array
    {
        return \App\Services\ContractService::customerRealization($this);
    }
}
