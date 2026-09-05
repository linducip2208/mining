<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class JournalEntry extends BaseModel
{
    protected $table = 'journal_entries';
    protected $guarded = ['id'];

    public function lines() { return $this->hasMany(JournalLine::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }

}
