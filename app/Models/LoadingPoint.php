<?php

namespace App\Models;

class LoadingPoint extends BaseModel
{
    protected $table = 'loading_points';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function site() { return $this->belongsTo(Site::class); }
    public function pit() { return $this->belongsTo(Pit::class); }
}
