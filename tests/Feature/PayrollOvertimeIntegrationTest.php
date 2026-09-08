<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\PayrollRun;
use App\Models\Setting;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollOvertimeIntegrationTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    protected function makeEmployee(float $basic = 3000000)
    {
        return Employee::create([
            'code' => 'EMP-'.uniqid(), 'name' => 'OT Tester', 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'basic_salary' => $basic, 'status' => 'ACTIVE',
            'join_date' => today()->startOfYear()->toDateString(),
        ]);
    }

    protected function makeRun(string $period): PayrollRun
    {
        return PayrollRun::create([
            'number' => 'PR-'.uniqid(), 'company_id' => $this->co->id,
            'period' => $period, 'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ]);
    }

    public function test_overtime_request_only_ignores_attendance_minutes(): void
    {
        Setting::set('payroll.overtime_source', 'OVERTIME_REQUEST_ONLY', 'string');
        $emp = $this->makeEmployee();
        $date = today()->startOfMonth()->toDateString();

        Attendance::create(['employee_id' => $emp->id, 'date' => $date, 'status' => 'PRESENT', 'overtime_minutes' => 240, 'late_minutes' => 0, 'created_by' => $this->admin->id]);
        Overtime::create(['number' => 'OT-'.uniqid(), 'employee_id' => $emp->id, 'date' => $date, 'hours' => 2, 'reason' => 'resmi', 'status' => 'APPROVED', 'approved_by' => $this->admin->id, 'created_by' => $this->admin->id]);

        $run = $this->makeRun(today()->format('Y-m'));
        PayrollService::calculate($run);
        $detail = $run->details()->where('employee_id', $emp->id)->firstOrFail();
        $ot = collect($detail->components['earnings'])->firstWhere('code', 'OVERTIME');

        $this->assertNotNull($ot);
        $this->assertEquals(2.0, $ot['hours']); // NOT 2 + 4h attendance = 6
    }

    public function test_attendance_only_uses_attendance_minutes(): void
    {
        Setting::set('payroll.overtime_source', 'ATTENDANCE_ONLY', 'string');
        $emp = $this->makeEmployee();
        $date = today()->startOfMonth()->toDateString();

        Attendance::create(['employee_id' => $emp->id, 'date' => $date, 'status' => 'PRESENT', 'overtime_minutes' => 120, 'late_minutes' => 0, 'created_by' => $this->admin->id]);
        Overtime::create(['number' => 'OT-'.uniqid(), 'employee_id' => $emp->id, 'date' => $date, 'hours' => 2, 'reason' => 'resmi', 'status' => 'APPROVED', 'approved_by' => $this->admin->id, 'created_by' => $this->admin->id]);

        $run = $this->makeRun(today()->format('Y-m'));
        PayrollService::calculate($run);
        $detail = $run->details()->where('employee_id', $emp->id)->firstOrFail();
        $ot = collect($detail->components['earnings'])->firstWhere('code', 'OVERTIME');

        $this->assertEquals(2.0, $ot['hours']);
    }

    public function test_merged_policy_never_sums_same_day_twice(): void
    {
        Setting::set('payroll.overtime_source', 'MERGED_NON_DUPLICATE', 'string');
        $emp = $this->makeEmployee();
        $date = today()->startOfMonth()->toDateString();

        // same day: attendance 1h + approved request 3h → must take max(1,3) = 3, NOT 4
        Attendance::create(['employee_id' => $emp->id, 'date' => $date, 'status' => 'PRESENT', 'overtime_minutes' => 60, 'late_minutes' => 0, 'created_by' => $this->admin->id]);
        Overtime::create(['number' => 'OT-A-'.uniqid(), 'employee_id' => $emp->id, 'date' => $date, 'hours' => 3, 'reason' => 'resmi', 'status' => 'APPROVED', 'approved_by' => $this->admin->id, 'created_by' => $this->admin->id]);
        // another day: only attendance 2h
        $date2 = today()->startOfMonth()->addDays(2)->toDateString();
        Attendance::create(['employee_id' => $emp->id, 'date' => $date2, 'status' => 'PRESENT', 'overtime_minutes' => 120, 'late_minutes' => 0, 'created_by' => $this->admin->id]);

        $hours = PayrollService::overtimeHours($emp, today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString());
        $this->assertEquals(5.0, $hours); // max(1,3) + 2 = 5 (double-count would give 8)
    }

    public function test_rejected_overtime_never_paid(): void
    {
        $emp = $this->makeEmployee();
        $date = today()->startOfMonth()->toDateString();
        Overtime::create(['number' => 'OT-R-'.uniqid(), 'employee_id' => $emp->id, 'date' => $date, 'hours' => 5, 'reason' => 'ditolak', 'status' => 'REJECTED', 'created_by' => $this->admin->id]);

        $run = $this->makeRun(today()->format('Y-m'));
        PayrollService::calculate($run);
        $detail = $run->details()->where('employee_id', $emp->id)->firstOrFail();

        $this->assertNull(collect($detail->components['earnings'])->firstWhere('code', 'OVERTIME'));
    }
}
