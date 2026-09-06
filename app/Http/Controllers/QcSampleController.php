<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Customer;
use App\Models\Item;
use App\Models\QcSample;
use App\Models\QualityParameter;
use App\Services\AuditService;
use App\Services\QualityService;
use Illuminate\Http\Request;

class QcSampleController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = QcSample::with(['item', 'customer', 'tests'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->orderByDesc('sample_date')->paginate(20)->withQueryString();
        return view('quality.samples.index', ['items' => $items, 'statuses' => ['PENDING', 'PASS', 'HOLD', 'REJECT']]);
    }

    public function create()
    {
        return view('quality.samples.form', [
            'sample' => null,
            'items' => Item::whereIn('type', ['PRODUCT', 'RAW'])->get(),
            'customers' => Customer::where('status', true)->get(),
            'batches' => \App\Models\ProductionBatch::orderByDesc('date')->limit(50)->get(),
            'deliveries' => \App\Models\DeliveryOrder::orderByDesc('id')->limit(50)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_type' => 'required|in:PRODUCTION_BATCH,STOCKPILE,SALES_ORDER,DELIVERY_ORDER',
            'source_id' => 'required|integer|min:1',
            'item_id' => 'nullable|exists:items,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sample_date' => 'required|date',
        ]);
        $sample = QualityService::createSample($validated);
        return redirect()->route('samples.show', $sample)->with('success', 'Sampel dibuat: ' . $sample->number);
    }

    public function show(QcSample $sample)
    {
        return view('quality.samples.show', [
            'sample' => $sample->load(['tests.parameter', 'item', 'customer']),
            'parameters' => QualityParameter::where('status', true)->get(),
        ]);
    }

    public function test(Request $request, QcSample $sample)
    {
        if (!auth()->user()->hasPermission('quality.test')) {
            abort(403);
        }
        $validated = $request->validate([
            'quality_parameter_id' => 'required|exists:quality_parameters,id',
            'result_value' => 'required|numeric',
            'notes' => 'nullable|max:1000',
        ]);
        try {
            $test = QualityService::recordTest($sample->id, (int) $validated['quality_parameter_id'], (float) $validated['result_value'], $validated['notes'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        $msg = 'Hasil tersimpan: ' . $test->result . '.';
        if ($test->result === 'FAIL') {
            $msg .= ' Otomatis HOLD — delivery terkait diblokir.';
        }
        return back()->with('success', $msg);
    }

    public function issueCoa(Request $request, QcSample $sample)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);
        try {
            $coa = QualityService::issueCoa($sample->id, $validated['customer_id'] ?? null, $validated['invoice_id'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'CoA diterbitkan: ' . $coa->number);
    }
}
