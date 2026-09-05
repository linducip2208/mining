<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AlertRule extends BaseModel
{
    protected $table = 'alert_rules';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['config' => 'array'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
