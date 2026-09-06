<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Item;
use App\Models\Site;
use App\Models\Stockpile;
use App\Models\StockpileSurvey;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\StockpileService;
use Illuminate\Http\Request;

class StockpileController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Stockpile::with(['site', 'item'])
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%")->orWhere('code', 'like', "%{$request->q}%"))
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->orderBy('code')->paginate(20)->withQueryString();
        foreach ($items as $pile) {
            $pile->balance = StockpileService::balance($pile->id);
        }
        return view('stockpile.index', ['items' => $items, 'sites' => Site::pluck('name', 'id')->all()]);
    }

    public function create()
    {
        return view('stockpile.form', [
            'pile' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'items' => Item::whereIn('type', ['PRODUCT', 'RAW'])->get(),
            'warehouses' => Warehouse::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'required|exists:sites,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'item_id' => 'nullable|exists:items,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'capacity_ton' => 'nullable|numeric|min:0',
            'survey_threshold_pct' => 'nullable|numeric|min:0|max:100',
            'opening' => 'nullable|numeric|min:0',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        $pile = \DB::transaction(function () use ($validated) {
            $pile = Stockpile::create($validated + ['status' => true, 'created_by' => auth()->id()]);
            if (!empty($validated['opening']) && $validated['opening'] > 0) {
                StockpileService::move($pile->id, 'OPENING', (float) $validated['opening'], 0, null, null, 'OPENING', now()->toDateString(), 'Saldo awal');
            }
            return $pile;
        });
        AuditService::created('STOCKPILE', $pile);
        return redirect()->route('stockpiles.show', $pile)->with('success', 'Stockpile dibuat.');
    }

    public function show(Stockpile $stockpile)
    {
        $movements = $stockpile->movements()->orderByDesc('trx_date')->orderByDesc('id')->limit(50)->get();
        $surveys = $stockpile->surveys()->orderByDesc('survey_date')->limit(10)->get();
        return view('stockpile.show', [
            'pile' => $stockpile->load(['site', 'item', 'warehouse']),
            'balance' => StockpileService::balance($stockpile->id),
            'movements' => $movements,
            'surveys' => $surveys,
        ]);
    }

    public function survey(Request $request, Stockpile $stockpile)
    {
        if (!auth()->user()->hasPermission('stockpile.reconcile')) {
            abort(403);
        }
        $validated = $request->validate([
            'survey_date' => 'required|date',
            'survey_balance' => 'required|numeric|min:0',
            'surveyor' => 'nullable|max:150',
        ]);
        try {
            $survey = StockpileService::survey($stockpile->id, $validated['survey_date'], (float) $validated['survey_balance'], $validated['surveyor'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        $msg = 'Survei tersimpan (variansi ' . $survey->variance . ' / ' . $survey->variance_pct . '%).';
        if ($survey->status === 'INVESTIGATE') {
            $msg .= ' Melewati threshold — investigasi wajib sebelum approve.';
        }
        return back()->with('success', $msg);
    }

    public function approveSurvey(Request $request, StockpileSurvey $survey)
    {
        if (!auth()->user()->hasPermission('stockpile.approve')) {
            abort(403);
        }
        $validated = $request->validate(['investigation' => 'nullable|max:2000']);
        try {
            StockpileService::approveSurvey($survey, $validated['investigation'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Rekonsiliasi disetujui — sistem selaras dengan survei.');
    }

    public function dashboard(Request $request)
    {
        $piles = Stockpile::with(['site', 'item'])
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->where('status', true)->get()
            ->map(function ($p) {
                $p->balance = StockpileService::balance($p->id);
                $p->fill_pct = $p->capacity_ton > 0 ? round($p->balance / $p->capacity_ton * 100, 1) : 0;
                $p->last_survey = $p->surveys()->latest('survey_date')->first();
                return $p;
            });
        return view('stockpile.dashboard', ['piles' => $piles, 'sites' => Site::pluck('name', 'id')->all()]);
    }
}
