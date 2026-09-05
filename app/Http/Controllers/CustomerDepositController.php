<?php

namespace App\Http\Controllers;

use App\Models\CashAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Services\AuditService;
use App\Services\DepositService;
use Illuminate\Http\Request;

class CustomerDepositController extends Controller
{
    public function index(Request $request)
    {
        $balances = Customer::where('status', true)
            ->selectRaw("customers.*, COALESCE((SELECT SUM(CASE WHEN movement_type='DEPOSIT_IN' THEN amount ELSE -amount END) FROM customer_deposits WHERE customer_deposits.customer_id = customers.id AND movement_type IN ('DEPOSIT_IN','DEPOSIT_USED','DEPOSIT_REFUND')), 0) as deposit_balance")
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%"))
            ->orderBy('name')->paginate(20)->withQueryString();

        $movements = CustomerDeposit::with(['customer', 'creator'])
            ->when($request->movement_type, fn ($q) => $q->where('movement_type', $request->movement_type))
            ->latest()->limit(50)->get();

        return view('sales.deposit.index', [
            'balances' => $balances,
            'movements' => $movements,
            'customers' => Customer::where('status', true)->get(),
            'cashAccounts' => CashAccount::where('status', true)->get(),
        ]);
    }

    public function depositIn(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'deposit_date' => 'required|date',
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'reference_no' => 'nullable|max:100',
            'notes' => 'nullable|max:500',
        ]);

        try {
            $deposit = DepositService::depositIn(
                (int) $validated['company_id'],
                (int) $validated['customer_id'],
                (float) $validated['amount'],
                $validated['deposit_date'],
                (int) $validated['cash_account_id'],
                $validated['reference_no'] ?? null,
                $validated['notes'] ?? null
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditService::created('FINANCE', $deposit);
        return back()->with('success', 'Deposit diterima: Rp ' . number_format($validated['amount'], 0, ',', '.'));
    }

    public function refund(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'deposit_date' => 'required|date',
            'cash_account_id' => 'required|exists:cash_accounts,id',
        ]);

        try {
            DepositService::refund(
                (int) $validated['company_id'],
                (int) $validated['customer_id'],
                (float) $validated['amount'],
                $validated['deposit_date'],
                (int) $validated['cash_account_id'],
                'REFUND-' . now()->format('YmdHis')
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Refund deposit diproses.');
    }

    public function statement(Customer $customer)
    {
        $ledger = CustomerDeposit::where('customer_id', $customer->id)->orderBy('deposit_date')->orderBy('id')->get();
        $balance = DepositService::balance($customer->id);

        return view('sales.deposit.statement', [
            'customer' => $customer,
            'ledger' => $ledger,
            'balance' => $balance,
        ]);
    }
}
