<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\HaulingContract;
use App\Models\HaulingRoute;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\NumberingService;
use Illuminate\Http\Request;

class HaulingContractController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = HaulingContract::with(['supplier', 'route'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('contract.hauling.index', ['items' => $items, 'statuses' => ['DRAFT', 'ACTIVE', 'COMPLETED', 'EXPIRED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('contract.hauling.form', [
            'contract' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'suppliers' => Supplier::where('status', true)->get(),
            'routes' => HaulingRoute::where('status', true)->get(),
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
            'hauling_route_id' => 'nullable|exists:hauling_routes,id',
            'rate_type' => 'required|in:PER_TON,PER_KM,PER_TRIP',
            'rate' => 'required|numeric|min:0',
            'minimum_volume' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $contract = HaulingContract::create($validated + [
            'number' => NumberingService::generate('CTR-H'),
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        AuditService::created('CONTRACT', $contract);
        return redirect()->route('hauling-contracts.index')->with('success', 'Kontrak hauling dibuat.');
    }

    public function approve(HaulingContract $hauling_contract)
    {
        if (!auth()->user()->hasPermission('contract.approve')) {
            abort(403);
        }
        if ($hauling_contract->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak valid.');
        }
        $hauling_contract->update(['status' => 'ACTIVE']);
        AuditService::log('APPROVE', 'CONTRACT', $hauling_contract->id, HaulingContract::class);
        return back()->with('success', 'Kontrak aktif.');
    }
}
