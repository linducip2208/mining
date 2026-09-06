<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\QualityHold;
use App\Services\AuditService;
use App\Services\QualityService;
use Illuminate\Http\Request;

class QualityHoldController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = QualityHold::with(['item', 'customer', 'salesOrder', 'deliveryOrder'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('quality.holds.index', ['items' => $items, 'statuses' => ['HOLD', 'RELEASED', 'CANCELLED']]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'delivery_order_id' => 'nullable|exists:delivery_orders,id',
            'item_id' => 'nullable|exists:items,id',
            'customer_id' => 'nullable|exists:customers,id',
            'reason' => 'required|max:2000',
        ]);
        QualityService::hold($validated);
        return back()->with('success', 'Quality hold dibuat — delivery terkait diblokir.');
    }

    public function release(Request $request, QualityHold $quality_hold)
    {
        if (!auth()->user()->hasPermission('quality.release')) {
            abort(403);
        }
        $validated = $request->validate(['note' => 'nullable|max:1000']);
        try {
            QualityService::release($quality_hold, $validated['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Hold di-release.');
    }

    public function specialApprove(Request $request, QualityHold $quality_hold)
    {
        if (!auth()->user()->hasPermission('quality.approve')) {
            abort(403);
        }
        $validated = $request->validate(['note' => 'required|max:1000']);
        try {
            QualityService::specialApprove($quality_hold, $validated['note']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditService::log('APPROVE', 'QUALITY', $quality_hold->id, QualityHold::class, null, ['special' => true]);
        return back()->with('success', 'Special approval tercatat — delivery dapat jalan.');
    }
}
