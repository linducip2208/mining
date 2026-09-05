<?php

namespace App\Services;

use App\Models\OperatorIncentive;
use App\Models\Setting;
use App\Models\PayrollComponent;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Calculate payroll for all active employees of a company for period YYYY-MM.
     * Net = Basic + Allowance + Overtime + Incentive + Bonus - Deduction - Loan - Tax
     */
    public static function calculate(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run) {
            if (!in_array($run->status, ['DRAFT', 'CALCULATED'])) {
                throw new \DomainException('Payroll tidak dapat dihitung pada status ' . $run->status);
            }

            [$y, $m] = explode('-', $run->period);
            $start = \Carbon\Carbon::create((int) $y, (int) $m, 1)->startOfDay();
            $end = $start->copy()->endOfMonth();

            $employees = \App\Models\Employee::where('company_id', $run->company_id)
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

                // attendance-based late deduction & overtime
                $attendances = $employee->attendances()->whereBetween('date', [$start, $end]);
                $overtimeMinutes = (clone $attendances)->sum('overtime_minutes');
                $overtimeHours = round($overtimeMinutes / 60, 2);

                $employeeRules = $employee->payrollRules()->with('component')->get();
                foreach ($employeeRules as $rule) {
                    $amount = (float) $rule->amount;
                    if ($rule->component->type === 'DEDUCTION' || $rule->operator === '-') {
                        $deductions[] = ['code' => $rule->component->code, 'name' => $rule->component->name, 'amount' => $amount];
                    } else {
                        $earnings[] = ['code' => $rule->component->code, 'name' => $rule->component->name, 'amount' => $amount];
                    }
                }

                // overtime pay: hourly = basic / 173
                if ($overtimeHours > 0) {
                    $hourly = $basic / 173;
                    $earnings[] = ['code' => 'OVERTIME', 'name' => 'Lembur', 'amount' => round($hourly * $overtimeHours * 1.5, 2), 'hours' => $overtimeHours];
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

                // simple PPh21 approximation: 5% of (gross - 54000000/12) if positive — configurable
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
     * Post payroll: Dr Salary Expense, Cr Salary Payable.
     */
    public static function post(PayrollRun $run): void
    {
        DB::transaction(function () use ($run) {
            if ($run->status !== 'APPROVED') {
                throw new \DomainException('Payroll harus APPROVED sebelum posting.');
            }
            [$y, $m] = explode('-', $run->period);
            $date = \Carbon\Carbon::create((int) $y, (int) $m, 1)->endOfMonth()->toDateString();

            $journal = AccountingService::post($run->company_id, $date, [
                ['code' => AccountingService::map('SALARY_EXPENSE'), 'debit' => $run->total_gross, 'memo' => 'Beban gaji ' . $run->period],
                ['code' => AccountingService::map('TAX_PPH21_PAYABLE'), 'credit' => $run->total_deduction, 'memo' => 'Potongan gaji ' . $run->period],
                ['code' => AccountingService::map('SALARY_PAYABLE'), 'credit' => $run->total_net, 'memo' => 'Gaji dibayar ' . $run->period],
            ], 'PAYROLL', $run->id, $run->number, 'Posting payroll ' . $run->period, 'PR');

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
            if ($run->status !== 'POSTED') {
                throw new \DomainException('Payroll belum diposting.');
            }
            [$y, $m] = explode('-', $run->period);
            $date = \Carbon\Carbon::create((int) $y, (int) $m, 1)->endOfMonth()->toDateString();

            AccountingService::post($run->company_id, $date, [
                ['code' => AccountingService::map('SALARY_PAYABLE'), 'debit' => $run->total_net, 'memo' => 'Pembayaran gaji ' . $run->period],
                ['code' => SalesService::cashCoa($cashAccountId), 'credit' => $run->total_net, 'memo' => 'Pembayaran gaji ' . $run->period],
            ], 'PAYROLL_PAYMENT', $run->id, $run->number, 'Pembayaran payroll ' . $run->period, 'PR');

            $run->status = 'PAID';
            $run->save();
        });
    }
}
