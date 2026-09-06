<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\FuelTank;
use App\Models\FuelTankDip;
use App\Services\FuelService;
use Illuminate\Http\Request;

class FuelDipController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = FuelTankDip::with(['tank'])
            ->when($request->fuel_tank_id, fn ($q) => $q->where('fuel_tank_id', $request->fuel_tank_id))
            ->orderByDesc('dip_date')->paginate(20)->withQueryString();
        return view('fuel.dips.index', [
            'items' => $items,
            'tanks' => FuelTank::where('status', true)->pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fuel_tank_id' => 'required|exists:fuel_tanks,id',
            'dip_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'dip_cm' => 'nullable|numeric|min:0',
            'physical_liter' => 'required|numeric|min:0',
            'notes' => 'nullable|max:1000',
        ]);
        $dip = new FuelTankDip($validated + ['measured_by' => auth()->id()]);
        FuelService::dip($dip);
        return back()->with('success', 'Hasil dip tersimpan. Variansi: ' . number_format($dip->variance, 2) . ' L.');
    }
}
