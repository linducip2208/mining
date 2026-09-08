<?php

namespace App\Services;

use App\Models\AccountingMapping;
use App\Models\Employee;
use App\Models\OperatorIncentive;
use App\Models\Overtime;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Overtime source policies (setting payroll.overtime_source):
     * - ATTENDANCE_ONLY: only attendance.overtime_minutes is counted.
     * - OVERTIME_REQUEST_ONLY (default, safe when approval workflow is used):
     *   only APPROVED Overtime records are counted; attendance minutes ignored
     *   so the same hours can never be paid twice.
     * - MERGED_NON_DUPLICATE: per calendar date the two sources are merged
     *   non-duplicatively (the larger of the two is taken, never the sum).
     */
    public static function overtimeSourcePolicy(): string
    {
        return strtoupper((string) Setting::get('payroll.overtime_source', 'OVERTIME_REQUEST_ONLY'));
    }

    /**
     * Resolve non-duplicated overtime hours for an employee within a period.
     */
    public static function overtimeHours(Employee $employee, string $start, string $end): float
    {
        $policy = self::overtimeSourcePolicy();

        if ($policy === 'ATTENDANCE_ONLY') {
            return round($employee->attendances()->whereBetween('date', [$start, $end])->sum('overtime_minutes') / 60, 2);
        }

        $approvedHours = round((float) Overtime::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->whereBetween('date', [$start, $end])
            ->sum('hours'), 2);

        if ($policy === 'OVERTIME_REQUEST_ONLY') {
            return $approvedHours;
        }

        // MERGED_NON_DUPLICATE: per-date max of (attendance minutes, approved overtime hours)
        $attendanceByDate = $employee->attendances()
            ->whereBetween('date', [$start, $end])
            ->where('overtime_minutes', '>', 0)
            ->pluck('overtime_minutes', 'date');
        $approvedByDate = Overtime::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->whereBetween('date', [$start, $end])
            ->selectRaw('date, SUM(hours) as hours')
            ->groupBy('date')
            ->pluck('hours', 'date');

        $dates = $attendanceByDate->keys()->merge($approvedByDate->keys())->unique();
        $hours = 0.0;
        foreach ($dates as $date) {
            $fromAttendance = round(((float) ($attendanceByDate[$date] ?? 0)) / 60, 2);
            $fromRequest = round((float) ($approvedByDate[$date] ?? 0), 2);
            $hours += max($fromAttendance, $fromRequest);
        }

        return round($hours, 2);
    }

    /**
     * Calculate payroll for all active employees of a company for period YYYY-MM.
     * Net = Basic + Allowance + Overtime + Incentive + Bonus - Deduction - Loan - Tax
     */
    public static function calculate(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run) {
            if (! in_array($run->status, ['DRAFT', 'CALCULATED'])) {
                throw new \DomainException('Payroll tidak dapat dihitung pada status '.$run->status);
            }

            [$y, $m] = explode('-', $run->period);
            $start = Carbon::create((int) $y, (int) $m, 1)->startOfDay();
            $end = $start->copy()->endOfMonth();

            $employees = Employee::where('company_id', $run->company_id)
                ->where('status', 'ACTIVE')
                ->whereDate('join_date', '<=', $end)
                ->get();

            $run->details()->delete();

            $totalGross = 0.0;
            $totalDeduction = 0.0;
            $totalNet = 0.0;

            foreach ($employees as $employee) {
                $earnings = [];
                $deductions = [];
                $basic = (float) $employee->basic_salary;
                $earnings[] = ['code' => 'BASIC', 'name' => 'Gaji Pokok', 'amount' => $basic];

                // overtime per configured source policy (never double-counted)
                $overtimeHours = self::overtimeHours($employee, $start->toDateString(), $end->toDateString());

                $employeeRules = $employee->payrollRules()->with('component')->get();
                foreach ($employeeRules as $rule) {
                    $amount = (float) $rule->amount;
                    if ($rule->component->type === 'DEDUCTION' || $rule->operator === '-') {
                        $deductions[] = ['code' => $rule->component->code, 'name' => $rule->component->name, 'amount' => $amount];
                    } else {
                        $earnings[] = ['code' => $rule->component->code, 'name' => $rule->component->name, 'amount' => $amount];
                    }
                }

                // overtime pay: hourly = basic / 173 × configurable rate
                if ($overtimeHours > 0) {
                    $hourly = $basic / 173;
                    $otRate = (float) Setting::get('payroll.overtime_rate', 1.5);
                    $earnings[] = ['code' => 'OVERTIME', 'name' => 'Lembur', 'amount' => round($hourly * $overtimeHours * $otRate, 2), 'hours' => $overtimeHours];
                }

                // approved operator incentives for this period
                $incentives = OperatorIncentive::where('employee_id', $employee->id)
                    ->where('period', $run->period)
                    ->where('status', 'APPROVED')
                    ->get();

                $incentiveTotal = 0.0;
                foreach ($incentives as $inc) {
                    $incentiveTotal += (float) $inc->amount;
                }
                if ($incentiveTotal > 0) {
                    $earnings[] = ['code' => 'INCENTIVE', 'name' => 'Insentif Operator', 'amount' => $incentiveTotal];
                    $incentives->each(fn ($i) => $i->update(['status' => 'INCLUDED_IN_PAYROLL']));
                }

                // BPJS contributions on basic salary (disabled unless configured)
                if (filter_var(Setting::get('payroll.bpjs_enabled', 'false'), FILTER_VALIDATE_BOOL)) {
                    $health = round($basic * ((float) Setting::get('payroll.bpjs_health_rate', 4) / 100), 2);
                    $employment = round($basic * ((float) Setting::get('payroll.bpjs_employment_rate', 3.37) / 100), 2);
                    if ($health > 0) {
                        $deductions[] = ['code' => 'BPJS_HEALTH', 'name' => 'BPJS Kesehatan', 'amount' => $health];
                    }
                    if ($employment > 0) {
                        $deductions[] = ['code' => 'BPJS_EMPLOYMENT', 'name' => 'BPJS Ketenagakerjaan', 'amount' => $employment];
                    }
                }

                // simple PPh21 approximation: rate of (gross - PTKP) if positive — configurable
                $gross = $basic + collect($earnings)->whereNotIn('code', ['BASIC'])->sum('amount');
                $ptkp = (float) Setting::get('payroll.ptkp_monthly', 4500000);
                $taxable = max(0, $gross - $ptkp);
                $tax = round($taxable * ((float) Setting::get('payroll.pph21_rate', 5) / 100), 2);
                if ($tax > 0) {
                    $deductions[] = ['code' => 'PPH21', 'name' => 'PPh 21', 'amount' => $tax];
                }

                $totalEarn = collect($earnings)->sum('amount');
                $totalDed = collect($deductions)->sum('amount');
                $net = $totalEarn - $totalDed;

                PayrollDetail::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basic,
                    'components' => ['earnings' => $earnings, 'deductions' => $deductions],
                    'total_earning' => $totalEarn,
                    'total_deduction' => $totalDed,
                    'net_salary' => $net,
                ]);

                $totalGross += $totalEarn;
                $totalDeduction += $totalDed;
                $totalNet += $net;
            }

            $run->total_gross = round($totalGross, 2);
            $run->total_deduction = round($totalDeduction, 2);
            $run->total_net = round($totalNet, 2);
            $run->employee_count = $employees->count();
            $run->status = 'CALCULATED';
            $run->save();

            AuditService::log('UPDATE', 'PAYROLL', $run->id, PayrollRun::class, null, ['status' => 'CALCULATED', 'net' => $run->total_net]);

            return $run;
        });
    }

    /**
     * Liability mapping per deduction component code.
     */
    public static function deductionMappingKey(string $componentCode): string
    {
        $code = strtoupper(trim($componentCode));

        return match (true) {
            $code === 'PPH21' || str_contains($code, 'PPH21') || str_contains($code, 'PPh') => 'TAX_PPH21_PAYABLE',
            str_contains($code, 'BPJS') && str_contains($code, 'HEALTH') => 'BPJS_HEALTH_PAYABLE',
            str_contains($code, 'BPJS') && str_contains($code, 'KETENAGAKERJAAN') => 'BPJS_EMPLOYMENT_PAYABLE',
            str_contains($code, 'BPJS') && str_contains($code, 'EMPLOYMENT') => 'BPJS_EMPLOYMENT_PAYABLE',
            str_contains($code, 'LOAN') || str_contains($code, 'PINJAMAN') => 'LOAN_RECEIVABLE',
            default => 'OTHER_PAYROLL_PAYABLE',
        };
    }

    /**
     * Post payroll journal with per-component liability split:
     * Dr Salary Expense (+ optional Overtime Expense)
     * Cr Tax Payable / BPJS payables / Loan receivable / Other payable (per component)
     * Cr Salary Payable (net).
     */
    public static function post(PayrollRun $run): void
    {
        DB::transaction(function () use ($run) {
            $run = PayrollRun::lockForUpdate()->find($run->id);
            if ($run->status !== 'APPROVED') {
                throw new \DomainException('Payroll harus APPROVED sebelum posting.');
            }
            [$y, $m] = explode('-', $run->period);
            $date = Carbon::create((int) $y, (int) $m, 1)->endOfMonth()->toDateString();

            $details = $run->details()->get();
            $overtimeAmount = 0.0;
            $expenseByCode = [];
            $deductionByMapping = [];
            foreach ($details as $detail) {
                $components = $detail->components ?? [];
                foreach (($components['earnings'] ?? []) as $earning) {
                    $amount = (float) ($earning['amount'] ?? 0);
                    if (($earning['code'] ?? '') === 'OVERTIME') {
                        $overtimeAmount += $amount;
                    } elseif (($earning['code'] ?? '') !== 'BASIC') {
                        // allowances/bonuses follow salary expense
                        $expenseByCode['SALARY_EXPENSE'] = ($expenseByCode['SALARY_EXPENSE'] ?? 0) + $amount;
                    }
                }
                foreach (($components['deductions'] ?? []) as $deduction) {
                    $key = self::deductionMappingKey((string) ($deduction['code'] ?? ''));
                    $deductionByMapping[$key] = ($deductionByMapping[$key] ?? 0) + (float) ($deduction['amount'] ?? 0);
                }
            }

            // Round-trip safety: unclassified remainder keeps the journal balanced.
            $classified = array_sum($deductionByMapping);
            $salaryExpense = round($run->total_gross - $overtimeAmount, 2);

            $lines = [];
            $lines[] = ['code' => AccountingService::map('SALARY_EXPENSE'), 'debit' => $salaryExpense, 'memo' => 'Beban gaji '.$run->period];
            if ($overtimeAmount > 0 && AccountingMapping::where('code', 'OVERTIME_EXPENSE')->exists()) {
                $lines[] = ['code' => AccountingService::map('OVERTIME_EXPENSE'), 'debit' => round($overtimeAmount, 2), 'memo' => 'Beban lembur '.$run->period];
            } else {
                // Overtime expense mapping not configured: keep it inside salary expense.
                $salaryExpense += round($overtimeAmount, 2);
                $lines[0]['debit'] = $salaryExpense;
            }

            foreach ($deductionByMapping as $key => $amount) {
                if (round($amount, 2) > 0) {
                    $lines[] = ['code' => AccountingService::map($key), 'credit' => round($amount, 2), 'memo' => 'Potongan '.strtolower(str_replace('_', ' ', $key)).' '.$run->period];
                }
            }
            $lines[] = ['code' => AccountingService::map('SALARY_PAYABLE'), 'credit' => $run->total_net, 'memo' => 'Gaji dibayar '.$run->period];

            $journal = AccountingService::post($run->company_id, $date, $lines, 'PAYROLL', $run->id, $run->number, 'Posting payroll '.$run->period, 'PR');

            $run->journal_entry_id = $journal->id;
            $run->status = 'POSTED';
            $run->posted_by = auth()->id();
            $run->posted_at = now();
            $run->save();

            AuditService::log('POST', 'PAYROLL', $run->id, PayrollRun::class, null, ['journal' => $journal->number]);
        });
    }

    /**
     * Payroll payment: Dr Salary Payable, Cr Bank.
     */
    public static function pay(PayrollRun $run, ?int $cashAccountId): void
    {
        DB::transaction(function () use ($run, $cashAccountId) {
            $run = PayrollRun::lockForUpdate()->find($run->id);
            if ($run->status !== 'POSTED') {
                throw new \DomainException('Payroll belum diposting.');
            }
            [$y, $m] = explode('-', $run->period);
            $date = Carbon::create((int) $y, (int) $m, 1)->endOfMonth()->toDateString();

            AccountingService::post($run->company_id, $date, [
                ['code' => AccountingService::map('SALARY_PAYABLE'), 'debit' => $run->total_net, 'memo' => 'Pembayaran gaji '.$run->period],
                ['code' => SalesService::cashCoa($cashAccountId), 'credit' => $run->total_net, 'memo' => 'Pembayaran gaji '.$run->period],
            ], 'PAYROLL_PAYMENT', $run->id, $run->number, 'Pembayaran payroll '.$run->period, 'PR');

            $run->status = 'PAID';
            $run->save();
        });
    }
}
