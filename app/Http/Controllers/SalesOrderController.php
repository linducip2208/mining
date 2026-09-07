<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Models\Site;
use App\Models\TaxCode;
use App\Services\ApprovalService;
use App\Services\AuditService;
use App\Services\ContractService;
use App\Services\NumberingService;
use App\Services\PriceService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = SalesOrder::with(['customer', 'items', 'company', 'invoice', 'deliveryOrders'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))

            ->when(! is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('sales.so.index', ['items' => $items, 'so' => null, 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'PARTIALLY_DELIVERED', 'COMPLETED', 'REJECTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('sales.so.form', [
            'so' => null,
            'customers' => Customer::where('status', true)->get(),
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'productItems' => Item::where('type', 'PRODUCT')->get(),
            'taxRate' => (float) TaxCode::where('code', Setting::get('tax.default_sales_tax_code', 'PPN11'))->value('rate'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        try {
            $so = DB::transaction(function () use ($validated, $request) {
                $so = SalesOrder::create($validated + [
                    'number' => NumberingService::generate('SO', $validated['company_id']),
                    'status' => 'DRAFT',
                    'created_by' => auth()->id(),
                ]);
                $this->syncLines($so, $request);

                return $so;
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditService::created('SALES', $so);

        return redirect()->route('sales-orders.index')->with('success', 'Order penjualan dibuat.');
    }

    public function show(SalesOrder $sales_order)
    {
        return view('sales.so.index', ['so' => $sales_order->load(['items.item', 'customer']), 'items' => SalesOrder::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'PARTIALLY_DELIVERED', 'COMPLETED', 'REJECTED', 'CANCELLED']]);
    }

    public function submit(SalesOrder $sales_order)
    {
        if ($sales_order->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat diajukan.');
        }
        ApprovalService::submit('SALES', 'SALES_ORDER', $sales_order);

        return back()->with('success', $sales_order->fresh()->status === 'SUBMITTED'
            ? 'SO diajukan ke approval center.'
            : 'SO disetujui.');
    }

    public function approve(SalesOrder $sales_order)
    {
        if (! auth()->user()->hasPermission('sales_order.approve')) {
            abort(403);
        }
        if ($sales_order->status !== 'SUBMITTED') {
            return back()->with('error', 'Status tidak valid.');
        }
        $sales_order->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'SALES', $sales_order->id, SalesOrder::class);

        return back()->with('success', 'SO disetujui.');
    }

    /**
     * Reserve stock for approved SO lines.
     */
    public function reserve(SalesOrder $sales_order)
    {
        if ($sales_order->status !== 'APPROVED') {
            return back()->with('error', 'Hanya SO yang disetujui dapat direservasi.');
        }
        try {
            $warehouse = Setting::get('inventory.default_warehouse_id');
            DB::transaction(function () use ($sales_order, $warehouse) {
                foreach ($sales_order->items as $line) {
                    $remaining = (float) $line->qty - (float) $line->qty_delivered;
                    if ($remaining > 0) {
                        StockService::reserve((int) $warehouse, $line->item_id, 'DO', $sales_order->id, $sales_order->number, $remaining);
                    }
                }
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stok direservasi untuk SO ini.');
    }

    protected function syncLines(SalesOrder $so, Request $request): void
    {
        $so->items()->delete();
        $subtotal = 0;
        foreach ($request->input('lines', []) as $line) {
            if (! empty($line['item_id']) && $line['qty'] > 0) {
                // contract guard: block over-contract lines without override permission
                ContractService::assertSalesWithinContract(
                    $so->customer_id, (int) $line['item_id'], (float) $line['qty'], $so->id,
                    $so->order_date?->toDateString()
                );
                $price = (float) ($line['unit_price'] ?: PriceService::resolvePrice($so->customer, $so->site_id, $line['item_id']));
                $total = round((float) $line['qty'] * $price, 2);
                $subtotal += $total;
                $so->items()->create(['item_id' => $line['item_id'], 'qty' => $line['qty'], 'unit_price' => $price, 'total_price' => $total]);
            }
        }
        $tax = round($subtotal * (float) TaxCode::where('code', Setting::get('tax.default_sales_tax_code', 'PPN11'))->value('rate') / 100, 2);
        $so->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax]);
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|max:2000',
        ]);
    }
}
