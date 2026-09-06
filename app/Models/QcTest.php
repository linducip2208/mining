<?php

namespace App\Models;

class QcTest extends BaseModel
{
    protected $table = 'qc_tests';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['result_value' => 'decimal:4'];
    }

    public function sample() { return $this->belongsTo(QcSample::class, 'qc_sample_id'); }
    public function parameter() { return $this->belongsTo(QualityParameter::class, 'quality_parameter_id'); }
}
