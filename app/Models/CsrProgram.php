<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CsrProgram extends BaseModel
{
    protected $table = 'csr_programs';
    protected $guarded = ['id'];

    public function activities() { return $this->hasMany(CsrActivity::class); }
    public function documents() { return $this->hasMany(CsrDocument::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }

}
