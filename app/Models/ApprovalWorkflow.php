<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ApprovalWorkflow extends BaseModel
{
    protected $table = 'approval_workflows';
    protected $guarded = ['id'];

    public function steps() { return $this->hasMany(ApprovalStep::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
