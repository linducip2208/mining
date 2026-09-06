<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\Tire;
use App\Services\AuditService;
use App\Services\TireService;
use Illuminate\Http\Request;

class TireController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Tire::with(['equipment', 'company'])
            ->when($request->q, fn ($q) => $q->where('serial_no', 'like', "%{$request->q}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('serial_no')->paginate(20)->withQueryString();
        return view('tire.index', ['items' => $items, 'statuses' => ['NEW', 'INSTALLED', 'REPAIR', 'STOCK', 'SCRAP']]);
    }

    public function create()
    {
        return view('tire.form', [
            'tire' => null,
            'companies' => Company::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('tire.create')) {
            abort(403);
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'serial_no' => 'required|max:50|unique:tires,serial_no',
            'brand' => 'nullable|max:100',
            'size' => 'nullable|max:50',
            'pattern' => 'nullable|max:50',
            'purchase_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $tire = Tire::create($validated + ['status' => 'NEW', 'created_by' => auth()->id()]);
        AuditService::created('TIRE', $tire);
        return redirect()->route('tires.show', $tire)->with('success', 'Ban terdaftar.');
    }

    public function show(Tire $tire)
    {
        return view('tire.show', [
            'tire' => $tire->load(['movements.equipment', 'equipment']),
            'units' => Equipment::whereNotIn('status', ['RETIRED', 'DISPOSED'])->orderBy('code')->get(),
        ]);
    }

    public function install(Request $request, Tire $tire)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'position' => 'required|max:30',
            'date' => 'required|date',
            'hm' => 'nullable|numeric|min:0',
        ]);
        $this->ensureInScope(Equipment::find($validated['equipment_id']));
        try {
            TireService::install($tire->id, (int) $validated['equipment_id'], $validated['position'], $validated['date'], (float) ($validated['hm'] ?? 0));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Ban dipasang.');
    }

    public function remove(Request $request, Tire $tire)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'reason' => 'required|max:255',
            'to_scrap' => 'boolean',
            'hm' => 'nullable|numeric|min:0',
        ]);
        try {
            TireService::remove($tire->id, $validated['date'], $validated['reason'], $request->boolean('to_scrap'), (float) ($validated['hm'] ?? 0));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Ban dilepas.');
    }

    public function rotate(Request $request, Tire $tire)
    {
        $validated = $request->validate([
            'position' => 'required|max:30',
            'date' => 'required|date',
        ]);
        try {
            TireService::rotate($tire->id, $validated['position'], $validated['date']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Ban dirotasi.');
    }

    public function repair(Request $request, Tire $tire)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'notes' => 'nullable|max:1000',
        ]);
        try {
            TireService::repair($tire->id, $validated['date'], (float) $validated['cost'], $validated['notes'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Repair tercatat.');
    }
}
