<?php

namespace App\Models;

class HseActivity extends BaseModel
{
    protected $table = 'hse_activities';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['activity_date' => 'date'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
}
