<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class LoginHistory extends BaseModel
{
    protected $table = 'login_history';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
