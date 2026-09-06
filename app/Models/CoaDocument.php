<?php

namespace App\Models;

class CoaDocument extends BaseModel
{
    protected $table = 'coa_documents';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'results' => 'array'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function sample() { return $this->belongsTo(QcSample::class, 'qc_sample_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
