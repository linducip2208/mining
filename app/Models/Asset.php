<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Asset extends BaseModel
{
    protected $table = 'assets';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }

}
