<?php

namespace App\Models;

class ProductSpecLine extends BaseModel
{
    protected $table = 'product_spec_lines';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['min_value' => 'decimal:4', 'max_value' => 'decimal:4', 'target_value' => 'decimal:4'];
    }

    public function specification() { return $this->belongsTo(ProductSpecification::class, 'product_specification_id'); }
    public function parameter() { return $this->belongsTo(QualityParameter::class, 'quality_parameter_id'); }
}
