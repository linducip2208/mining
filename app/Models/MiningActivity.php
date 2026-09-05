<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MiningActivity extends BaseModel
{
    protected $table = 'mining_activities';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function site() { return $this->belongsTo(Site::class); }
    public function pit() { return $this->belongsTo(Pit::class); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function operator() { return $this->belongsTo(Employee::class, 'operator_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function company() { return $this->belongsTo(Company::class); }

}
