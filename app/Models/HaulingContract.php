<?php

namespace App\Models;

class HaulingContract extends BaseModel
{
    protected $table = 'hauling_contracts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'minimum_volume' => 'decimal:4',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function route() { return $this->belongsTo(HaulingRoute::class, 'hauling_route_id'); }
}
