<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\Site;
use Illuminate\Http\Request;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Generic CRUD for master data with search, filters, pagination, soft delete.
 * Subclasses configure: $model, $viewPrefix, $rules, $searchColumns, $sortable, $with.
 */
abstract class BaseCrudController extends Controller
{
    use AppliesDataScope;

    protected string $model;
    protected string $viewPrefix;
    protected string $module;
    protected array $rules = [];
    protected array $searchColumns = ['code', 'name'];
    protected array $with = [];

    public function index(Request $request)
    {
        $model = $this->model;
        $q = $model::query()->with($this->with);

        if ($search = $request->q) {
            $q->where(function (Builder $b) use ($search) {
                foreach ($this->searchColumns as $col) {
                    $b->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        $this->applyFilters($q, $request);

        $sort = $request->get('sort', 'id');
        $dir = $request->get('dir', 'desc');
        if (in_array($sort, array_merge($this->searchColumns, ['id', 'created_at', 'date', 'status']))) {
            $q->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->latest();
        }

        $items = $q->paginate(20)->withQueryString();

        if ($request->boolean('export')) {
            return $this->exportCsv($q);
        }

        $data = $this->indexData($request);
        return view("{$this->viewPrefix}.index", array_merge(['items' => $items], $data));
    }

    protected function applyFilters(Builder $q, Request $request): void
    {
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('company_id')) {
            $q->where('company_id', $request->company_id);
        }
        if ($request->filled('site_id')) {
            $q->where('site_id', $request->site_id);
        }
        // auto data-scope: batasi ke company/site user bila kolom tersedia
        if ($this->modelHasColumn('company_id')) {
            $this->applyCompanyScope($q);
        }
        if ($this->modelHasColumn('site_id')) {
            $this->applySiteScope($q);
        }
    }

    protected static array $scopeColumnCache = [];

    protected function modelHasColumn(string $column): bool
    {
        $table = (new $this->model)->getTable();
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$scopeColumnCache)) {
            self::$scopeColumnCache[$key] = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        }
        return self::$scopeColumnCache[$key];
    }

    protected function indexData(Request $request): array
    {
        return $this->lookups();
    }

    protected function lookups(): array
    {
        return [
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'divisions' => \App\Models\Division::pluck('name', 'id')->all(),
            'itemCategories' => \App\Models\ItemCategory::pluck('name', 'id')->all(),
            'units' => \App\Models\Unit::pluck('name', 'id')->all(),
            'paymentTerms' => \App\Models\PaymentTerm::pluck('name', 'id')->all(),
            'employees' => \App\Models\Employee::pluck('name', 'id')->all(),
            'coas' => \App\Models\ChartOfAccount::where('is_postable', true)->orderBy('code')->pluck('name', 'id')->all(),
        ];
    }

    protected function formData($item = null): array
    {
        return $this->lookups();
    }

    public function create()
    {
        return view("{$this->viewPrefix}.form", ['item' => null] + $this->formData());
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission($this->module . '.create')) {
            abort(403);
        }
        $validated = $request->validate($this->rules());
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $item = $this->model::create($validated + ['created_by' => auth()->id()]);
        AuditService::created($this->module, $item);
        return redirect()->route("{$this->viewPrefix}.index")->with('success', 'Data berhasil disimpan.');
    }

    public function show($id)
    {
        $item = $this->model::with($this->with)->findOrFail($id);
        $this->ensureInScope($item);
        return view("{$this->viewPrefix}.show", ['item' => $item] + $this->formData($item));
    }

    public function edit($id)
    {
        $item = $this->model::findOrFail($id);
        $this->ensureInScope($item);
        return view("{$this->viewPrefix}.form", ['item' => $item] + $this->formData($item));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermission($this->module . '.update')) {
            abort(403);
        }
        $item = $this->model::findOrFail($id);
        $old = $item->toArray();
        $validated = $request->validate($this->rules($item));
        $this->ensureInScope($item);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $item->update($validated + ['updated_by' => auth()->id()]);
        AuditService::updated($this->module, $item, $old);
        return redirect()->route("{$this->viewPrefix}.index")->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasPermission($this->module . '.delete')) {
            abort(403);
        }
        $item = $this->model::findOrFail($id);
        $this->ensureInScope($item);
        AuditService::deleted($this->module, $item);
        $item->delete();
        return redirect()->route("{$this->viewPrefix}.index")->with('success', 'Data berhasil dihapus.');
    }

    protected function rules($item = null): array
    {
        return $this->rules;
    }

    protected function exportCsv($query)
    {
        $filename = $this->viewPrefix . '-' . now()->format('YmdHis') . '.csv';
        $rows = $query->limit(5000)->get();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($out, array_keys($rows->first()->toArray()));
                foreach ($rows as $row) {
                    fputcsv($out, $row->toArray());
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
