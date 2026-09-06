<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\FuelTank;
use App\Models\FuelTransfer;
use App\Services\AuditService;
use App\Services\FuelService;
use Illuminate\Http\Request;

class FuelTransferController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = FuelTransfer::with(['fromTank', 'toTank'])
            ->orderByDesc('transfer_date')->paginate(20)->withQueryString();
        return view('fuel.transfers.index', ['items' => $items, 'statuses' => ['DRAFT', 'POSTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('fuel.transfers.form', [
            'transfer' => null,
            'tanks' => FuelTank::where('status', true)->pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('fuel.create')) {
            abort(403);
        }
        $validated = $request->validate([
            'from_tank_id' => 'required|different:to_tank_id|exists:fuel_tanks,id',
            'to_tank_id' => 'required|exists:fuel_tanks,id',
            'transfer_date' => 'required|date',
            'liter' => 'required|numeric|min:0.001',
            'notes' => 'nullable|max:1000',
        ]);
        $transfer = FuelTransfer::create($validated + [
            'number' => \App\Services\NumberingService::generate('FUEL-T'),
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        AuditService::created('FUEL', $transfer);
        return redirect()->route('fuel-transfers.index')->with('success', 'Transfer dibuat.');
    }

    public function post(FuelTransfer $fuel_transfer)
    {
        if (!auth()->user()->hasPermission('fuel.post')) {
            abort(403);
        }
        try {
            FuelService::transfer($fuel_transfer);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Transfer diposting.');
    }
}
