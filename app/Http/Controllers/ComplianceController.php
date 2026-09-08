<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\ComplianceRegister;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Site;
use App\Services\ComplianceService;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = ComplianceRegister::with(['employee', 'equipment', 'site'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('title', 'like', "%{$request->q}%")->orWhere('document_number', 'like', "%{$request->q}%"));
        $this->applyCompanyScope($items);
        $this->applySiteScope($items);
        $items = $items->orderBy('expiry_date')->paginate(20)->withQueryString();

        return view('compliance.index', [
            'items' => $items,
            'types' => ['PERMIT' => 'Izin', 'LICENSE' => 'Lisensi', 'EMP_CERT' => 'Sertifikasi Karyawan', 'EQUIP_CERT' => 'Sertifikasi Alat', 'ENVIRONMENT' => 'Lingkungan', 'CONTRACT' => 'Kontrak', 'OTHER' => 'Lainnya'],
            'statuses' => ['ACTIVE', 'EXPIRING_SOON', 'EXPIRED', 'RENEWED', 'CANCELLED'],
        ]);
    }

    public function create()
    {
        return view('compliance.form', [
            'item' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'employees' => Employee::where('status', 'ACTIVE')->get(),
            'units' => Equipment::orderBy('code')->get(),
            'types' => ['PERMIT' => 'Izin', 'LICENSE' => 'Lisensi', 'EMP_CERT' => 'Sertifikasi Karyawan', 'EQUIP_CERT' => 'Sertifikasi Alat', 'ENVIRONMENT' => 'Lingkungan', 'CONTRACT' => 'Kontrak', 'OTHER' => 'Lainnya'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:PERMIT,LICENSE,EMP_CERT,EQUIP_CERT,ENVIRONMENT,CONTRACT,OTHER',
            'title' => 'required|max:255',
            'document_number' => 'nullable|max:100',
            'company_id' => 'nullable|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'employee_id' => 'nullable|exists:employees,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'document_id' => 'nullable|exists:documents,id',
            'issued_date' => 'nullable|date',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'responsible_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('compliance', 'private');
        }
        $reg = ComplianceService::register($validated);

        return redirect()->route('compliance.index')->with('success', 'Register tersimpan: '.$reg->number);
    }

    public function show(ComplianceRegister $compliance)
    {
        return view('compliance.show', ['item' => $compliance->load(['employee', 'equipment', 'document', 'site'])]);
    }

    public function renew(Request $request, ComplianceRegister $compliance)
    {
        if (! auth()->user()->hasPermission('compliance.update')) {
            abort(403);
        }
        $validated = $request->validate([
            'expiry_date' => 'required|date|after:today',
            'document_number' => 'nullable|max:100',
        ]);
        try {
            ComplianceService::renew($compliance, $validated['expiry_date'], $validated['document_number'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Diperpanjang hingga '.$validated['expiry_date'].'.');
    }

    public function calendar(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to = $request->to ?? now()->addDays(120)->toDateString();
        $rows = ComplianceService::calendar($from, $to,
            $request->company_id ? (int) $request->company_id : null,
            $request->site_id ? (int) $request->site_id : null);

        return view('compliance.calendar', [
            'rows' => $rows, 'from' => $from, 'to' => $to,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }
}
