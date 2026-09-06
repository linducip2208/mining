<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\FuelReceipt;
use App\Models\FuelTank;
use App\Models\Site;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\FuelService;
use Illuminate\Http\Request;

class FuelReceiptController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = FuelReceipt::with(['tank', 'supplier'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->orderByDesc('receipt_date')->paginate(20)->withQueryString();
        return view('fuel.receipts.index', ['items' => $items, 'statuses' => ['DRAFT', 'POSTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('fuel.receipts.form', [
            'receipt' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'tanks' => FuelTank::where('status', true)->get(),
            'suppliers' => Supplier::where('status', true)->get(),
            'pos' => \App\Models\PurchaseOrder::where('status', 'APPROVED')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('fuel.create')) {
            abort(403);
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'fuel_tank_id' => 'required|exists:fuel_tanks,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'receipt_date' => 'required|date',
            'liter' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0',
            'delivery_note' => 'nullable|max:100',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        $validated['number'] = \App\Services\NumberingService::generate('FUEL-R', $validated['company_id']);
        $validated['total_cost'] = round((float) $validated['liter'] * (float) $validated['unit_price'], 2);
        $validated['status'] = 'DRAFT';
        $validated['created_by'] = auth()->id();
        $receipt = FuelReceipt::create($validated);
        AuditService::created('FUEL', $receipt);
        return redirect()->route('fuel-receipts.index')->with('success', 'Penerimaan BBM dibuat.');
    }

    public function post(FuelReceipt $fuel_receipt)
    {
        if (!auth()->user()->hasPermission('fuel.post')) {
            abort(403);
        }
        try {
            FuelService::receive($fuel_receipt);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Penerimaan diposting — stok tangki + & jurnal AP tersimpan.');
    }
}
