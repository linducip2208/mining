<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\Item;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\NumberingService;
use Illuminate\Http\Request;

class CustomerContractController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = CustomerContract::with(['customer', 'item'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('contract.customers.index', ['items' => $items, 'statuses' => ['DRAFT', 'ACTIVE', 'COMPLETED', 'EXPIRED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('contract.customers.form', [
            'contract' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'customers' => Customer::where('status', true)->get(),
            'items' => Item::where('type', 'PRODUCT')->get(),
            'sites' => Site::pluck('name', 'id')->all(),
            'specs' => \App\Models\ProductSpecification::where('status', 'ACTIVE')->get(),
            'paymentTerms' => \App\Models\PaymentTerm::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('contract.create')) {
            abort(403);
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'item_id' => 'required|exists:items,id',
            'site_id' => 'nullable|exists:sites,id',
            'contract_qty' => 'required|numeric|min:0.0001',
            'price' => 'required|numeric|min:0',
            'pricing_formula' => 'nullable|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'delivery_term' => 'nullable|max:100',
            'product_specification_id' => 'nullable|exists:product_specifications,id',
            'tax_code' => 'nullable|max:20',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $contract = CustomerContract::create($validated + [
            'number' => NumberingService::generate('CTR-C'),
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        AuditService::created('CONTRACT', $contract);
        return redirect()->route('customer-contracts.show', $contract)->with('success', 'Kontrak dibuat.');
    }

    public function show(CustomerContract $customer_contract)
    {
        return view('contract.customers.show', [
            'contract' => $customer_contract->load(['customer', 'item', 'site']),
            'real' => $customer_contract->realization(),
        ]);
    }

    public function approve(CustomerContract $customer_contract)
    {
        if (!auth()->user()->hasPermission('contract.approve')) {
            abort(403);
        }
        if ($customer_contract->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak valid.');
        }
        $customer_contract->update(['status' => 'ACTIVE', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'CONTRACT', $customer_contract->id, CustomerContract::class);
        return back()->with('success', 'Kontrak aktif.');
    }
}
