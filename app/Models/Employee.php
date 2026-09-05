<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Employee extends BaseModel
{
    protected $table = 'employees';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'birth_date' => 'date',
            'join_date' => 'date',
            'end_date' => 'date',
        ];
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function division() { return $this->belongsTo(Division::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function scopeActive($q){return $q->where("status","ACTIVE");}
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function overtimeRecords() { return $this->hasMany(Overtime::class); }
    public function payrollRules() { return $this->hasMany(EmployeePayrollRule::class); }
    public function incentives() { return $this->hasMany(OperatorIncentive::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
}
