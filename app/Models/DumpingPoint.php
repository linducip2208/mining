<?php

namespace App\Models;

class DumpingPoint extends BaseModel
{
    protected $table = 'dumping_points';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function site() { return $this->belongsTo(Site::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function crusher() { return $this->belongsTo(Crusher::class); }
}
