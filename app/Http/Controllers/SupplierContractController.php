<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Services\AuditService;
use App\Services\NumberingService;
use Illuminate\Http\Request;

class SupplierContractController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = SupplierContract::with(['supplier', 'item'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('contract.suppliers.index', ['items' => $items, 'statuses' => ['DRAFT', 'ACTIVE', 'COMPLETED', 'EXPIRED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('contract.suppliers.form', [
            'contract' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'suppliers' => Supplier::where('status', true)->get(),
            'items' => Item::orderBy('name')->get(),
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
            'supplier_id' => 'required|exists:suppliers,id',
            'item_id' => 'nullable|exists:items,id',
            'service_description' => 'nullable|max:255',
            'contract_qty' => 'nullable|numeric|min:0',
            'contract_value' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'sla' => 'nullable|max:2000',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $contract = SupplierContract::create($validated + [
            'number' => NumberingService::generate('CTR-S'),
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        AuditService::created('CONTRACT', $contract);
        return redirect()->route('supplier-contracts.show', $contract)->with('success', 'Kontrak dibuat.');
    }

    public function show(SupplierContract $supplier_contract)
    {
        return view('contract.suppliers.show', [
            'contract' => $supplier_contract->load(['supplier', 'item']),
            'real' => $supplier_contract->realization(),
        ]);
    }

    public function approve(SupplierContract $supplier_contract)
    {
        if (!auth()->user()->hasPermission('contract.approve')) {
            abort(403);
        }
        if ($supplier_contract->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak valid.');
        }
        $supplier_contract->update(['status' => 'ACTIVE', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'CONTRACT', $supplier_contract->id, SupplierContract::class);
        return back()->with('success', 'Kontrak aktif.');
    }
}
