<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\Crusher;
use App\Models\Item;
use App\Models\Site;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Models\ProductionBatch;
use App\Services\AuditService;
use App\Services\OperationsService;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionBatchController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = ProductionBatch::with(['crusher', 'shift', 'operator'])
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from, fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('date', '<=', $request->to))

            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->orderByDesc('date')->paginate(20)->withQueryString();

        return view('production.index', [
            'items' => $items,
            'sites' => Site::pluck('name', 'id')->all(),
            'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'CANCELLED'],
        ]);
    }

    public function create()
    {
        return view('production.form', $this->refs());
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        $batch = DB::transaction(function () use ($validated, $request) {
            $validated['number'] = \App\Services\NumberingService::generate('PB', $validated['company_id']);
            $validated['status'] = 'DRAFT';
            $validated['created_by'] = auth()->id();

            $batch = ProductionBatch::create($validated);
            $this->syncLines($batch, $request);
            $this->recalc($batch);

            return $batch;
        });

        AuditService::created('PRODUCTION', $batch);
        return redirect()->route('production-batches.show', $batch)->with('success', 'Batch produksi dibuat.');
    }

    public function show(ProductionBatch $production_batch)
    {
        $production_batch->load(['crusher', 'shift', 'operator', 'inputs.item', 'outputs.item', 'losses', 'scraps.item']);
        return view('production.show', ['batch' => $production_batch]);
    }

    public function edit(ProductionBatch $production_batch)
    {
        if ($production_batch->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat diubah.');
        }
        return view('production.form', ['batch' => $production_batch->load(['inputs', 'outputs', 'losses', 'scraps'])] + $this->refs());
    }

    public function update(Request $request, ProductionBatch $production_batch)
    {
        if ($production_batch->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat diubah.');
        }
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        DB::transaction(function () use ($production_batch, $validated, $request) {
            $production_batch->update($validated + ['updated_by' => auth()->id()]);
            $production_batch->inputs()->delete();
            $production_batch->outputs()->delete();
            $production_batch->losses()->delete();
            $production_batch->scraps()->delete();
            $this->syncLines($production_batch, $request);
            $this->recalc($production_batch);
        });

        AuditService::updated('PRODUCTION', $production_batch);
        return redirect()->route('production-batches.show', $production_batch)->with('success', 'Batch diperbarui.');
    }

    public function destroy(ProductionBatch $production_batch)
    {
        if ($production_batch->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat dihapus.');
        }
        AuditService::deleted('PRODUCTION', $production_batch);
        $production_batch->delete();
        return redirect()->route('production-batches.index')->with('success', 'Batch dihapus.');
    }

    public function submit(ProductionBatch $production_batch)
    {
        ApprovalService::submit('PRODUCTION', 'PRODUCTION_BATCH', $production_batch);
        return back()->with('success', 'Batch diajukan untuk persetujuan.');
    }

    public function approve(ProductionBatch $production_batch)
    {
        if (!auth()->user()->hasPermission('production.approve')) {
            abort(403);
        }
        if ($production_batch->status !== 'SUBMITTED') {
            return back()->with('error', 'Status tidak valid.');
        }
        $production_batch->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'PRODUCTION', $production_batch->id, ProductionBatch::class);
        return back()->with('success', 'Batch disetujui.');
    }

    public function post(ProductionBatch $production_batch)
    {
        try {
            OperationsService::post($production_batch);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Batch diposting: stok in/out & jurnal tersimpan.');
    }

    protected function syncLines(ProductionBatch $batch, Request $request): void
    {
        $items = Item::all()->keyBy('id');
        foreach ($request->input('inputs', []) as $row) {
            if (!empty($row['item_id']) && $row['tonnage'] > 0) {
                $batch->inputs()->create(['item_id' => $row['item_id'], 'warehouse_id' => $row['warehouse_id'] ?? null, 'tonnage' => $row['tonnage']]);
            }
        }
        foreach ($request->input('outputs', []) as $row) {
            if (!empty($row['item_id']) && $row['gross_tonnage'] > 0) {
                $batch->outputs()->create([
                    'item_id' => $row['item_id'],
                    'warehouse_id' => $row['warehouse_id'] ?? null,
                    'gross_tonnage' => $row['gross_tonnage'],
                    'net_tonnage' => $row['gross_tonnage'],
                ]);
            }
        }
        foreach ($request->input('losses', []) as $row) {
            if (!empty($row['tonnage']) && $row['tonnage'] > 0) {
                $batch->losses()->create(['category' => $row['category'], 'tonnage' => $row['tonnage'], 'notes' => $row['notes'] ?? null]);
            }
        }
        foreach ($request->input('scraps', []) as $row) {
            if (!empty($row['tonnage']) && $row['tonnage'] > 0) {
                $batch->scraps()->create(['item_id' => $row['item_id'] ?? null, 'warehouse_id' => $row['warehouse_id'] ?? null, 'tonnage' => $row['tonnage']]);
            }
        }
    }

    /**
     * NET OUTPUT = GROSS OUTPUT - LOSS - SCRAP.
     */
    protected function recalc(ProductionBatch $batch): void
    {
        $batch->refresh();
        $input = (float) $batch->inputs()->sum('tonnage');
        $gross = (float) $batch->outputs()->sum('gross_tonnage');
        $loss = (float) $batch->losses()->sum('tonnage');
        $scrap = (float) $batch->scraps()->sum('tonnage');

        $batch->input_tonnage = $input;
        $batch->gross_output = $gross;
        $batch->total_loss = $loss;
        $batch->total_scrap = $scrap;
        $batch->net_output = max(0, round($gross - $loss - $scrap, 4));

        // distribute net tonnage across outputs proportionally
        if ($gross > 0) {
            foreach ($batch->outputs as $out) {
                $out->net_tonnage = round((float) $out->gross_tonnage * $batch->net_output / $gross, 4);
                $out->save();
            }
        }
        $batch->save();
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'required|exists:sites,id',
            'crusher_id' => 'required|exists:crushers,id',
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'operator_id' => 'nullable|exists:employees,id',
            'start_time' => 'nullable|date',
            'finish_time' => 'nullable|date|after:start_time',
            'notes' => 'nullable|max:2000',
        ]);
    }

    protected function refs(): array
    {
        return [
            'batch' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'crushers' => Crusher::all(),
            'shifts' => Shift::all(),
            'operators' => Employee::where('status', 'ACTIVE')->get(),
            'rawItems' => Item::where('type', 'RAW')->get(),
            'productItems' => Item::whereIn('type', ['PRODUCT'])->get(),
            'warehouses' => Warehouse::all(),
            'lossCategories' => ['DEBU' => 'Debu', 'MOISTURE' => 'Moisture', 'WASTE' => 'Waste', 'PROCESS_LOSS' => 'Process Loss', 'ADJUSTMENT' => 'Penyesuaian'],
        ];
    }
}
