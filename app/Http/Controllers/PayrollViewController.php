<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\OperatorIncentive;
use App\Models\PayrollRun;
use App\Models\PayrollDetail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\ApprovalService;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollViewController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = PayrollRun::with(['company'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))

            ->when(!is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('hr.payroll.index', ['items' => $items, 'payrollRun' => null, 'statuses' => ['DRAFT', 'CALCULATED', 'APPROVED', 'POSTED', 'PAID', 'CANCELLED']]);
    }

    public function create()
    {
        return view('hr.payroll.form', [
            'payrollRun' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'defaultPeriod' => now()->format('Y-m'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'period' => 'required|date_format:Y-m',
        ]);
        $exists = PayrollRun::where('company_id', $validated['company_id'])->where('period', $validated['period'])->exists();
        if ($exists) {
            return back()->with('error', 'Payroll periode tersebut sudah ada.');
        }
        $run = PayrollRun::create([
            'number' => \App\Services\NumberingService::generate('PYR', $validated['company_id']),
            'company_id' => $validated['company_id'],
            'period' => $validated['period'],
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        AuditService::created('PAYROLL', $run);
        return redirect()->route('payroll-runs.show', $run)->with('success', 'Payroll dibuat. Jalankan kalkulasi.');
    }

    public function show(PayrollRun $payroll_run)
    {
        return view('hr.payroll.index', [
            'payrollRun' => $payroll_run->load(['details.employee', 'company']),
            'items' => PayrollRun::orderByDesc('id')->paginate(20),
            'statuses' => ['DRAFT', 'CALCULATED', 'APPROVED', 'POSTED', 'PAID', 'CANCELLED'],
        ]);
    }

    public function calculate(PayrollRun $payroll_run)
    {
        try {
            PayrollService::calculate($payroll_run);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Payroll dikalkulasi: ' . $payroll_run->employee_count . ' karyawan, Net Rp ' . number_format($payroll_run->total_net, 0, ',', '.'));
    }

    public function approve(PayrollRun $payroll_run)
    {
        if (!auth()->user()->hasPermission('payroll.approve')) {
            abort(403);
        }
        if ($payroll_run->status !== 'CALCULATED') {
            return back()->with('error', 'Status tidak valid.');
        }
        $payroll_run->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'PAYROLL', $payroll_run->id, PayrollRun::class);
        return back()->with('success', 'Payroll disetujui.');
    }

    public function post(PayrollRun $payroll_run)
    {
        try {
            PayrollService::post($payroll_run);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Payroll diposting — jurnal beban gaji tersimpan.');
    }

    public function pay(Request $request, PayrollRun $payroll_run)
    {
        $validated = $request->validate(['cash_account_id' => 'nullable|exists:cash_accounts,id']);
        try {
            PayrollService::pay($payroll_run, $validated['cash_account_id'] ?? null);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Pembayaran gaji diposting.');
    }

    public function payslip(PayrollRun $payroll_run, PayrollDetail $detail)
    {
        return view('hr.payroll.payslip', ['run' => $payroll_run, 'detail' => $detail->load('employee')]);
    }
}
