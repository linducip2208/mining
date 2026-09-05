<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AuditLog extends BaseModel
{
    protected $table = 'audit_logs';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function user() { return $this->belongsTo(User::class); }

}
