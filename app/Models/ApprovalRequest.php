<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ApprovalRequest extends BaseModel
{
    protected $table = 'approval_requests';
    protected $guarded = ['id'];

    public function actions() { return $this->hasMany(ApprovalAction::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function workflow() { return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id'); }
public function transaction() { return $this->transaction_type === "custom" ? null : app(App\Services\ApprovalResolver::class)->resolve($this); }
}
