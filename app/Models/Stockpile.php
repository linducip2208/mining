<?php

namespace App\Models;

class Stockpile extends BaseModel
{
    protected $table = 'stockpiles';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['capacity_ton' => 'decimal:2', 'survey_threshold_pct' => 'decimal:2'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function movements() { return $this->hasMany(StockpileMovement::class); }
    public function surveys() { return $this->hasMany(StockpileSurvey::class); }
}
