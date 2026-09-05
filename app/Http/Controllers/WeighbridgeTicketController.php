<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Weighbridge;
use App\Models\WeighbridgeTicket;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeighbridgeTicketController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = WeighbridgeTicket::with(['weighbridge', 'customer', 'item', 'operator'])
            ->when($request->q, fn ($q) => $q->where('ticket_no', 'like', "%{$request->q}%")->orWhere('vehicle_plate', 'like', "%{$request->q}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->direction, fn ($q) => $q->where('direction', $request->direction))
            ->when($request->from, fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('created_at', '<=', $request->to))

            ->when(!is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('weighbridge.index', [
            'items' => $items,
            'statuses' => ['FIRST_WEIGH', 'COMPLETE', 'VALIDATED', 'POSTED', 'CANCELLED', 'VOID'],
        ]);
    }

    public function create()
    {
        return view('weighbridge.form', $this->refs());
    }

    /**
     * First weigh: gross in / tare out.
     */
    public function firstWeigh(Request $request)
    {
        $validated = $request->validate([
            'weighbridge_id' => 'required|exists:weighbridges,id',
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'required|exists:sites,id',
            'direction' => 'required|in:IN,OUT',
            'vehicle_plate' => 'required|max:30',
            'driver_name' => 'nullable|max:150',
            'weight' => 'required|numeric|min:1',
        ]);

        $ticket = DB::transaction(function () use ($validated) {
            $wb = Weighbridge::with('calibrations')->find($validated['weighbridge_id']);
            $active = $wb->calibrations()->orderByDesc('calibration_date')->first();

            return WeighbridgeTicket::create([
                'ticket_no' => \App\Services\NumberingService::generate('WB', $validated['company_id'], $validated['site_id']),
                'weighbridge_id' => $validated['weighbridge_id'],
                'company_id' => $validated['company_id'],
                'site_id' => $validated['site_id'],
                'direction' => $validated['direction'],
                'first_weigh_at' => now(),
                'first_weight' => $validated['weight'],
                'vehicle_plate' => $validated['vehicle_plate'],
                'driver_name' => $validated['driver_name'] ?? null,
                'calibration_version' => $active?->version,
                'operator_id' => auth()->id(),
                'status' => 'FIRST_WEIGH',
                'created_by' => auth()->id(),
            ]);
        });

        AuditService::created('WEIGHBRIDGE', $ticket);
        return redirect()->route('weighbridge-tickets.show', $ticket)->with('success', 'Timbang pertama tersimpan. Ticket: ' . $ticket->ticket_no);
    }

    public function show(WeighbridgeTicket $weighbridge_ticket)
    {
        $weighbridge_ticket->load(['weighbridge', 'customer', 'supplier', 'item', 'operator']);
        return view('weighbridge.show', ['ticket' => $weighbridge_ticket]);
    }

    /**
     * Second weigh: completes GROSS/TARE/NET.
     */
    public function secondWeigh(Request $request, WeighbridgeTicket $weighbridge_ticket)
    {
        if ($weighbridge_ticket->status !== 'FIRST_WEIGH') {
            return back()->with('error', 'Ticket tidak dalam status timbang pertama.');
        }

        $validated = $request->validate([
            'weight' => 'required|numeric|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'item_id' => 'nullable|exists:items,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $first = (float) $weighbridge_ticket->first_weight;
        $second = (float) $validated['weight'];

        if (bccomp((string) $first, (string) $second, 4) === 0) {
            return back()->with('error', 'Berat kedua tidak boleh sama dengan berat pertama.');
        }

        $weighbridge_ticket->update([
            'second_weigh_at' => now(),
            'second_weight' => $second,
            'gross' => max($first, $second),
            'tare' => min($first, $second),
            'net' => round(max($first, $second) - min($first, $second), 4),
            'customer_id' => $validated['customer_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'item_id' => $validated['item_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'status' => 'COMPLETE',
        ]);

        AuditService::updated('WEIGHBRIDGE', $weighbridge_ticket);
        return back()->with('success', 'Timbang kedua tersimpan. NET: ' . number_format($weighbridge_ticket->net, 2) . ' kg');
    }

    /**
     * Manual weight override — requires permission + reason (audited).
     */
    public function overrideWeight(Request $request, WeighbridgeTicket $weighbridge_ticket)
    {
        if (!filter_var(\App\Models\Setting::get('weighbridge.allow_weight_override', 'true'), FILTER_VALIDATE_BOOL)) {
            return back()->with('error', 'Override berat dinonaktifkan oleh pengaturan sistem.');
        }

        $validated = $request->validate([
            'gross' => 'required|numeric|min:0',
            'tare' => 'required|numeric|min:0',
            'override_reason' => 'required|max:500',
        ]);

        if ($validated['tare'] > $validated['gross']) {
            return back()->with('error', 'Tare tidak boleh lebih besar dari gross.');
        }

        $weighbridge_ticket->update([
            'gross' => $validated['gross'],
            'tare' => $validated['tare'],
            'net' => round($validated['gross'] - $validated['tare'], 4),
            'weight_overridden' => true,
            'override_reason' => $validated['override_reason'],
        ]);

        AuditService::log('UPDATE', 'WEIGHBRIDGE', $weighbridge_ticket->id, WeighbridgeTicket::class, null, [
            'override' => ['gross' => $validated['gross'], 'tare' => $validated['tare'], 'net' => $weighbridge_ticket->net],
        ], $validated['override_reason']);

        return back()->with('success', 'Berat dioverride dengan alasan tercatat.');
    }

    public function postTicket(WeighbridgeTicket $weighbridge_ticket)
    {
        if (!in_array($weighbridge_ticket->status, ['COMPLETE', 'VALIDATED'])) {
            return back()->with('error', 'Ticket belum lengkap.');
        }
        $weighbridge_ticket->update(['status' => 'POSTED']);
        AuditService::log('POST', 'WEIGHBRIDGE', $weighbridge_ticket->id, WeighbridgeTicket::class, null, ['ticket' => $weighbridge_ticket->ticket_no, 'net' => $weighbridge_ticket->net]);
        return back()->with('success', 'Ticket diposting.');
    }

    /**
     * Void requires approval workflow when configured.
     */
    public function void(Request $request, WeighbridgeTicket $weighbridge_ticket)
    {
        $validated = $request->validate(['cancel_reason' => 'required|max:500']);
        $requireApproval = filter_var(\App\Models\Setting::get('weighbridge.void_require_approval', 'true'), FILTER_VALIDATE_BOOL);

        if ($requireApproval) {
            $wf = \App\Models\ApprovalWorkflow::where('transaction_type', 'WEIGHBRIDGE_VOID')->where('is_active', true)->first();
            if ($wf) {
                $weighbridge_ticket->update(['cancel_reason' => $validated['cancel_reason']]);
                ApprovalService::submit('WEIGHBRIDGE', 'WEIGHBRIDGE_VOID', $weighbridge_ticket);
                return back()->with('success', 'Permintaan void diajukan untuk persetujuan.');
            }
        }

        $weighbridge_ticket->update(['status' => 'VOID', 'cancel_reason' => $validated['cancel_reason']]);
        AuditService::log('VOID', 'WEIGHBRIDGE', $weighbridge_ticket->id, WeighbridgeTicket::class, null, ['ticket' => $weighbridge_ticket->ticket_no], $validated['cancel_reason']);
        return back()->with('success', 'Ticket di-void.');
    }

    /**
     * Print / reprint with audit.
     */
    public function printTicket(WeighbridgeTicket $weighbridge_ticket)
    {
        $weighbridge_ticket->increment('reprint_count');
        AuditService::log('PRINT', 'WEIGHBRIDGE', $weighbridge_ticket->id, WeighbridgeTicket::class, null, ['ticket' => $weighbridge_ticket->ticket_no, 'reprint_count' => $weighbridge_ticket->reprint_count]);
        return view('weighbridge.print', ['ticket' => $weighbridge_ticket->load(['weighbridge', 'customer', 'item'])]);
    }

    public function destroy(WeighbridgeTicket $weighbridge_ticket)
    {
        if ($weighbridge_ticket->status !== 'FIRST_WEIGH') {
            return back()->with('error', 'Hanya ticket belum selesai yang dapat dihapus.');
        }
        AuditService::deleted('WEIGHBRIDGE', $weighbridge_ticket);
        $weighbridge_ticket->delete();
        return redirect()->route('weighbridge-tickets.index')->with('success', 'Ticket dihapus.');
    }

    protected function refs(): array
    {
        return [
            'ticket' => null,
            'weighbridges' => Weighbridge::all(),
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'customers' => Customer::where('status', true)->get(),
            'suppliers' => Supplier::where('status', true)->get(),
            'items' => Item::all(),
            'vehicles' => Equipment::where('type', 'DUMP_TRUCK')->get(),
        ];
    }
}
