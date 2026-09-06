<?php

namespace App\Models;

class HsePermit extends BaseModel
{
    protected $table = 'hse_permits';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['valid_from' => 'datetime', 'valid_until' => 'datetime'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function requester() { return $this->belongsTo(Employee::class, 'requester_id'); }
}
