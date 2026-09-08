<?php

namespace Tests\Feature;

use App\Models\AccountingMapping;
use App\Models\Employee;
use App\Models\EmployeePayrollRule;
use App\Models\JournalEntry;
use App\Models\PayrollComponent;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAccountingTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    protected function makeEmployee(float $basic): Employee
    {
        return Employee::create([
            'code' => 'EMP-'.uniqid(), 'name' => 'Pay Tester', 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'basic_salary' => $basic, 'status' => 'ACTIVE',
            'join_date' => today()->startOfYear()->toDateString(),
        ]);
    }

    protected function makeRunAndPost(float $basic, array $ruleComponents = []): JournalEntry
    {
        $emp = $this->makeEmployee($basic);
        foreach ($ruleComponents as $component) {
            EmployeePayrollRule::create(['employee_id' => $emp->id, 'payroll_component_id' => $component['component']->id, 'amount' => $component['amount'], 'operator' => '-']);
        }
        $run = PayrollRun::create([
            'number' => 'PR-'.uniqid(), 'company_id' => $this->co->id,
            'period' => today()->format('Y-m'), 'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ]);
        PayrollService::calculate($run);
        $run->update(['status' => 'APPROVED', 'approved_by' => $this->admin->id]);
        PayrollService::post($run);
        $run->refresh();

        return JournalEntry::findOrFail($run->journal_entry_id);
    }

    protected function makeComponent(string $code, string $name, string $type): PayrollComponent
    {
        return PayrollComponent::create(['code' => $code, 'name' => $name, 'type' => $type, 'status' => true]);
    }

    protected function coaId(string $mappingKey): int
    {
        return (int) AccountingMapping::where('code', $mappingKey)->value('chart_of_account_id');
    }

    protected function map(string $mappingKey): int
    {
        return $this->coaId($mappingKey);
    }

    public function test_pph21_goes_to_tax_payable_not_salary_payable(): void
    {
        $journal = $this->makeRunAndPost(20000000);
        $pphLine = $journal->lines()->where('chart_of_account_id', $this->map('TAX_PPH21_PAYABLE'))->first();
        $this->assertNotNull($pphLine, 'PPh21 harus masuk ke hutang PPh21, bukan hutang gaji');
        $this->assertGreaterThan(0, (float) $pphLine->credit);
    }

    public function test_loan_deduction_goes_to_loan_receivable(): void
    {
        $loanComponent = $this->makeComponent('LOAN', 'Cicilan Pinjaman', 'DEDUCTION');
        $journal = $this->makeRunAndPost(5000000, [['component' => $loanComponent, 'amount' => 1000000]]);

        $loanLine = $journal->lines()->where('chart_of_account_id', $this->map('LOAN_RECEIVABLE'))->first();
        $this->assertNotNull($loanLine, 'Potongan pinjaman harus mengurangi piutang karyawan');
        $this->assertEquals(1000000.0, (float) $loanLine->credit);

        // PPh21 must only contain the tax amount, not the loan
        $pphLine = $journal->lines()->where('chart_of_account_id', $this->map('TAX_PPH21_PAYABLE'))->first();
        $this->assertEquals(25000.0, (float) $pphLine->credit); // (5jt - 4.5jt PTKP) × 5%
    }

    public function test_bpjs_deduction_goes_to_bpjs_payable(): void
    {
        $bpjs = $this->makeComponent('BPJS_KOPEL', 'Koperasi BPJS', 'DEDUCTION');
        $journal = $this->makeRunAndPost(5000000, [['component' => $bpjs, 'amount' => 150000]]);

        $otherLine = $journal->lines()->where('chart_of_account_id', $this->map('OTHER_PAYROLL_PAYABLE'))->first();
        $this->assertNotNull($otherLine, 'Potongan non-pajak non-loan masuk hutang potongan lain');
        $this->assertEquals(150000.0, (float) $otherLine->credit);
    }

    public function test_journal_still_balanced_with_mixed_deductions(): void
    {
        $loan = $this->makeComponent('LOAN', 'Cicilan Pinjaman', 'DEDUCTION');
        $bpjs = $this->makeComponent('BPJS_KOPEL', 'Koperasi BPJS', 'DEDUCTION');
        $journal = $this->makeRunAndPost(10000000, [
            ['component' => $loan, 'amount' => 800000],
            ['component' => $bpjs, 'amount' => 200000],
        ]);

        $d = (float) $journal->lines()->sum('debit');
        $c = (float) $journal->lines()->sum('credit');
        $this->assertEquals(0, bccomp((string) $d, (string) $c, 2));
        $this->assertEquals(10000000.0, $d); // gross
    }

    public function test_pay_clears_salary_payable(): void
    {
        $emp = $this->makeEmployee(4000000);
        $run = PayrollRun::create([
            'number' => 'PR-'.uniqid(), 'company_id' => $this->co->id,
            'period' => today()->format('Y-m'), 'status' => 'DRAFT', 'created_by' => $this->admin->id,
        ]);
        PayrollService::calculate($run);
        $run->update(['status' => 'APPROVED']);
        PayrollService::post($run);
        PayrollService::pay($run->fresh(), null);

        $run->refresh();
        $this->assertEquals('PAID', $run->status);
        $paymentJournal = JournalEntry::where('source_type', 'PAYROLL_PAYMENT')->firstOrFail();
        $salaryPayableDebit = (float) $paymentJournal->lines()
            ->where('chart_of_account_id', $this->map('SALARY_PAYABLE'))
            ->where('debit', '>', 0)->sum('debit');
        $this->assertEquals((float) $run->total_net, $salaryPayableDebit);
    }
}
