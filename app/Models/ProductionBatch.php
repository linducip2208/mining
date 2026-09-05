<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ProductionBatch extends BaseModel
{
    protected $table = 'production_batches';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function inputs() { return $this->hasMany(ProductionInput::class); }
    public function outputs() { return $this->hasMany(ProductionOutput::class); }
    public function losses() { return $this->hasMany(ProductionLoss::class); }
    public function scraps() { return $this->hasMany(ProductionScrap::class); }
    public function crusher() { return $this->belongsTo(Crusher::class); }
    public function operator() { return $this->belongsTo(Employee::class, 'operator_id'); }
    public function shift() { return $this->belongsTo(Shift::class); }

}
