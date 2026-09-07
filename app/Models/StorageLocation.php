<?php

namespace App\Models;

class StorageLocation extends BaseModel
{
    protected $table = 'storage_locations';

    protected $guarded = ['id'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function fullCode(): string
    {
        return $this->code ?: trim("{$this->zone}-{$this->rack}-{$this->bin}", '-');
    }
}
