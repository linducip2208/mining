<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class EmployeePayrollRule extends BaseModel
{
    protected $table = 'employee_payroll_rules';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function component() { return $this->belongsTo(PayrollComponent::class, 'payroll_component_id'); }

}
