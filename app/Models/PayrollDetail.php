<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PayrollDetail extends BaseModel
{
    protected $table = 'payroll_details';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'components' => 'array',
            'total_earning' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function run() { return $this->belongsTo(PayrollRun::class, 'payroll_run_id'); }

}
