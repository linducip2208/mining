<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\Site;
use App\Models\Warehouse;
use App\Models\VendorBill;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\GoodsReceipt;
use App\Services\AuditService;
use App\Services\ApprovalService;
use App\Services\OperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = PurchaseRequest::with(['items', 'company', 'creator'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))

            ->when(!is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('procurement.pr.index', ['items' => $items, 'pr' => null, 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('procurement.pr.form', [
            'pr' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'items' => \App\Models\Item::orderBy('name')->get(),
            'divisions' => \App\Models\Division::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $pr = DB::transaction(function () use ($validated, $request) {
            $pr = PurchaseRequest::create([
                'number' => \App\Services\NumberingService::generate('PR', $validated['company_id']),
                'company_id' => $validated['company_id'],
                'site_id' => $validated['site_id'] ?? null,
                'division_id' => $validated['division_id'] ?? null,
                'request_date' => $validated['request_date'],
                'required_date' => $validated['required_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
            foreach ($request->input('lines', []) as $line) {
                if (!empty($line['item_id']) && $line['qty'] > 0) {
                    $pr->items()->create(['item_id' => $line['item_id'], 'qty' => $line['qty'], 'remark' => $line['remark'] ?? null]);
                }
            }
            return $pr;
        });
        AuditService::created('PROCUREMENT', $pr);
        return redirect()->route('purchase-requests.index')->with('success', 'Permintaan pembelian dibuat.');
    }

    public function show(PurchaseRequest $purchase_request)
    {
        return view('procurement.pr.index', ['pr' => $purchase_request->load(['items.item', 'company', 'site']), 'items' => PurchaseRequest::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CANCELLED']]);
    }

    public function edit(PurchaseRequest $purchase_request)
    {
        if ($purchase_request->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat diubah.');
        }
        return view('procurement.pr.form', ['pr' => $purchase_request->load('items'), 'companies' => Company::pluck('name', 'id')->all(), 'sites' => Site::pluck('name', 'id')->all(), 'items' => \App\Models\Item::orderBy('name')->get(), 'divisions' => \App\Models\Division::pluck('name', 'id')->all()]);
    }

    public function update(Request $request, PurchaseRequest $purchase_request)
    {
        if ($purchase_request->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat diubah.');
        }
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        DB::transaction(function () use ($purchase_request, $validated, $request) {
            $purchase_request->update($validated + ['updated_by' => auth()->id()]);
            $purchase_request->items()->delete();
            foreach ($request->input('lines', []) as $line) {
                if (!empty($line['item_id']) && $line['qty'] > 0) {
                    $purchase_request->items()->create(['item_id' => $line['item_id'], 'qty' => $line['qty'], 'remark' => $line['remark'] ?? null]);
                }
            }
        });
        AuditService::updated('PROCUREMENT', $purchase_request);
        return redirect()->route('purchase-requests.index')->with('success', 'PR diperbarui.');
    }

    public function submit(PurchaseRequest $purchase_request)
    {
        ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $purchase_request);
        return back()->with('success', 'PR diajukan untuk persetujuan.');
    }

    public function approve(PurchaseRequest $purchase_request)
    {
        if (!auth()->user()->hasPermission('purchase_request.approve')) {
            abort(403);
        }
        $purchase_request->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'PROCUREMENT', $purchase_request->id, PurchaseRequest::class);
        return back()->with('success', 'PR disetujui.');
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'division_id' => 'nullable|exists:divisions,id',
            'request_date' => 'required|date',
            'required_date' => 'nullable|date',
            'notes' => 'nullable|max:2000',
        ]);
    }
}
