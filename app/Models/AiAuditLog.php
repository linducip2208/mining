<?php

namespace App\Models;

class AiAuditLog extends BaseModel
{
    protected $table = 'ai_audit_logs';
    protected $guarded = ['id'];
    public $timestamps = false;
    public $updated_at = false;

    protected function casts(): array
    {
        return ['data_sources' => 'array', 'created_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }
}
