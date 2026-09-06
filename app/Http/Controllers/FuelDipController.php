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
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), function ($w) use ($sites) {
                $w->whereHas('tank', fn ($t) => $t->whereIn('site_id', $sites));
            })
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
        try {
            FuelService::dip($dip);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Hasil dip tersimpan. Variansi: ' . number_format($dip->variance, 2) . ' L.');
    }

    public function approve(FuelTankDip $dip)
    {
        if (!auth()->user()->hasPermission('fuel.approve')) {
            abort(403);
        }
        try {
            FuelService::approveDip($dip);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Selisih dip di-approve (susut tercatat).');
    }
}
