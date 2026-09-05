<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\DeliveryOrder;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\SalesOrder;
use App\Models\WeighbridgeTicket;
use App\Services\AuditService;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = DeliveryOrder::with(['salesOrder.customer', 'salesOrder.invoice', 'warehouse', 'ticket'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('sales.do.index', ['items' => $items, 'do' => null, 'statuses' => ['DRAFT', 'LOADING', 'COMPLETED', 'CANCELLED']]);
    }

    public function create(Request $request)
    {
        $soId = $request->integer('sales_order_id');
        return view('sales.do.form', [
            'do' => null,
            'salesOrders' => SalesOrder::whereIn('status', ['APPROVED', 'PARTIALLY_DELIVERED'])->with(['customer', 'items'])->get(),
            'warehouses' => Warehouse::pluck('name', 'id')->all(),
            'vehicles' => Equipment::where('type', 'DUMP_TRUCK')->get(),
            'soId' => $soId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'delivery_date' => 'required|date',
            'vehicle_id' => 'nullable|exists:equipment,id',
            'vehicle_plate' => 'nullable|max:30',
            'driver_name' => 'nullable|max:150',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty_ordered' => 'required|numeric|min:0.0001',
        ]);

        $so = SalesOrder::with('items')->find($validated['sales_order_id']);
        $this->ensureInScope($so);
        if (!in_array($so->status, ['APPROVED', 'PARTIALLY_DELIVERED'])) {
            return back()->with('error', 'SO belum disetujui.');
        }

        // prevent over-delivery
        foreach ($validated['lines'] as $line) {
            $soItem = $so->items->where('item_id', $line['item_id'])->first();
            if (!$soItem) {
                return back()->with('error', 'Item tidak ada dalam SO.');
            }
            $remaining = (float) $soItem->qty - (float) $soItem->qty_delivered;
            if ((float) $line['qty_ordered'] > $remaining + 0.0001) {
                return back()->with('error', "Quantity melebihi sisa SO untuk item {$soItem->item->code} (sisa: {$remaining}).");
            }
        }

        $do = DB::transaction(function () use ($validated, $so) {
            $do = DeliveryOrder::create([
                'number' => \App\Services\NumberingService::generate('DO', $so->company_id),
                'sales_order_id' => $so->id,
                'warehouse_id' => $validated['warehouse_id'],
                'delivery_date' => $validated['delivery_date'],
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'total_qty' => collect($validated['lines'])->sum('qty_ordered'),
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['lines'] as $line) {
                $do->items()->create(['item_id' => $line['item_id'], 'qty_ordered' => $line['qty_ordered']]);
            }
            return $do;
        });

        AuditService::created('SALES', $do);
        return redirect()->route('delivery-orders.index')->with('success', 'Surat jalan dibuat. Lanjutkan ke timbangan untuk finalisasi.');
    }

    public function show(DeliveryOrder $delivery_order)
    {
        return view('sales.do.index', ['do' => $delivery_order->load(['items.item', 'salesOrder.customer', 'ticket']), 'items' => DeliveryOrder::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'LOADING', 'COMPLETED', 'CANCELLED']]);
    }

    /**
     * Complete DO with weighbridge net weight (stock out + SO update).
     */
    public function complete(Request $request, DeliveryOrder $delivery_order)
    {
        $validated = $request->validate([
            'weighbridge_ticket_id' => 'required|exists:weighbridge_tickets,id',
        ]);

        $ticket = WeighbridgeTicket::find($validated['weighbridge_ticket_id']);
        if (!in_array($ticket->status, ['COMPLETE', 'VALIDATED'])) {
            return back()->with('error', 'Tiket timbangan belum lengkap.');
        }

        try {
            SalesService::completeDelivery($delivery_order, $ticket);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Surat jalan selesai — stok keluar ' . number_format($ticket->net, 2) . ' kg.');
    }
}
