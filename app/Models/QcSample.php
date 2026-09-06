<?php

namespace App\Models;

class QcSample extends BaseModel
{
    protected $table = 'qc_samples';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sample_date' => 'date', 'tested_at' => 'datetime'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function tests() { return $this->hasMany(QcTest::class); }
}
