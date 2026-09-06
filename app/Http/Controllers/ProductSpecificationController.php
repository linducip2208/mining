<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ProductSpecification;
use App\Models\QualityParameter;
use App\Models\Site;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSpecificationController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = ProductSpecification::with(['item', 'customer'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('effective_date')->paginate(20)->withQueryString();
        return view('quality.specs.index', ['items' => $items, 'statuses' => ['ACTIVE', 'EXPIRED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('quality.specs.form', [
            'spec' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'items' => Item::whereIn('type', ['PRODUCT', 'RAW'])->get(),
            'customers' => Customer::where('status', true)->get(),
            'sites' => Site::pluck('name', 'id')->all(),
            'parameters' => QualityParameter::where('status', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'item_id' => 'required|exists:items,id',
            'customer_id' => 'nullable|exists:customers,id',
            'site_id' => 'nullable|exists:sites,id',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'lines' => 'required|array|min:1',
            'lines.*.quality_parameter_id' => 'required|exists:quality_parameters,id',
            'lines.*.min_value' => 'nullable|numeric',
            'lines.*.max_value' => 'nullable|numeric',
            'lines.*.target_value' => 'nullable|numeric',
        ]);
        $spec = DB::transaction(function () use ($validated) {
            $spec = ProductSpecification::create($validated + ['status' => 'ACTIVE', 'created_by' => auth()->id()]);
            foreach ($validated['lines'] as $line) {
                $spec->lines()->create($line);
            }
            return $spec;
        });
        AuditService::created('QUALITY', $spec);
        return redirect()->route('specs.index')->with('success', 'Spesifikasi dibuat.');
    }

    public function show(ProductSpecification $spec)
    {
        return view('quality.specs.index', [
            'spec' => $spec->load(['lines.parameter', 'item', 'customer']),
            'items' => ProductSpecification::orderByDesc('id')->paginate(20),
            'statuses' => ['ACTIVE', 'EXPIRED', 'CANCELLED'],
        ]);
    }
}
