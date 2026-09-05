<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Site;
use App\Models\PriceList;
use App\Models\PriceVariance;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceListController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = PriceList::with(['items', 'customer', 'site', 'approvedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('sales.price.index', ['items' => $items, 'priceList' => null, 'statuses' => ['DRAFT', 'APPROVED', 'EXPIRED', 'CANCELLED'], 'types' => ['STANDARD', 'CUSTOMER', 'SITE', 'CONTRACT', 'RETAIL', 'SPECIAL']]);
    }

    public function create()
    {
        return view('sales.price.form', [
            'priceList' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'customers' => Customer::where('status', true)->get(),
            'sites' => Site::pluck('name', 'id')->all(),
            'items' => Item::where('type', 'PRODUCT')->get(),
            'types' => ['STANDARD', 'CUSTOMER', 'SITE', 'CONTRACT', 'RETAIL', 'SPECIAL'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $priceList = DB::transaction(function () use ($validated, $request) {
            $priceList = PriceList::create($validated + ['status' => 'DRAFT', 'created_by' => auth()->id()]);
            foreach ($request->input('lines', []) as $line) {
                if (!empty($line['item_id']) && isset($line['price'])) {
                    $priceList->items()->create(['item_id' => $line['item_id'], 'price' => $line['price'], 'min_qty' => $line['min_qty'] ?? 0]);
                }
            }
            return $priceList;
        });
        AuditService::created('PRICE', $priceList);
        return redirect()->route('price-lists.index')->with('success', 'Daftar harga dibuat.');
    }

    public function show(PriceList $price_list)
    {
        return view('sales.price.index', ['priceList' => $price_list->load(['items.item', 'customer']), 'items' => PriceList::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'APPROVED', 'EXPIRED', 'CANCELLED'], 'types' => ['STANDARD', 'CUSTOMER', 'SITE', 'CONTRACT', 'RETAIL', 'SPECIAL']]);
    }

    public function approve(PriceList $price_list)
    {
        if (!auth()->user()->hasPermission('price.approve')) {
            abort(403);
        }
        $price_list->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);

        // price history
        foreach ($price_list->items as $line) {
            \App\Models\PriceHistory::create([
                'item_id' => $line->item_id,
                'customer_id' => $price_list->customer_id,
                'site_id' => $price_list->site_id,
                'old_price' => 0,
                'new_price' => $line->price,
                'effective_date' => $price_list->effective_date,
                'reason' => 'Price list ' . $price_list->code,
                'created_by' => auth()->id(),
            ]);
        }

        AuditService::log('APPROVE', 'PRICE', $price_list->id, PriceList::class);
        return back()->with('success', 'Daftar harga disetujui & aktif.');
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'type' => 'required|in:STANDARD,CUSTOMER,SITE,CONTRACT,RETAIL,SPECIAL',
            'customer_id' => 'nullable|exists:customers,id',
            'site_id' => 'nullable|exists:sites,id',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'reason' => 'nullable|max:500',
        ]);
    }
}
