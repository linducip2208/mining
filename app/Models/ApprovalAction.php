<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ApprovalAction extends BaseModel
{
    protected $table = 'approval_actions';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function request() { return $this->belongsTo(ApprovalRequest::class, 'approval_request_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }

}
