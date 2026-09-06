<?php

namespace App\Models;

class HaulingRoute extends BaseModel
{
    protected $table = 'hauling_routes';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['distance_km' => 'decimal:2'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function site() { return $this->belongsTo(Site::class); }
    public function loadingPoint() { return $this->belongsTo(LoadingPoint::class); }
    public function dumpingPoint() { return $this->belongsTo(DumpingPoint::class); }
}
