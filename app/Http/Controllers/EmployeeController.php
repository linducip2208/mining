<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\Division;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Site;
use App\Models\CostCenter;
use App\Models\Employee;
use App\Services\AuditService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Employee::with(['company', 'site', 'division', 'department'])
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%")->orWhere('code', 'like', "%{$request->q}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->orderBy('code')->paginate(20)->withQueryString();

        return view('hr.employee.index', [
            'items' => $items,
            'employee' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'divisions' => Division::pluck('name', 'id')->all(),
            'departments' => Department::pluck('name', 'id')->all(),
            'shifts' => Shift::pluck('name', 'id')->all(),
            'costCenters' => CostCenter::pluck('name', 'id')->all(),
            'statuses' => ['ACTIVE', 'INACTIVE', 'TERMINATED'],
        ]);
    }

    public function create()
    {
        return view('hr.employee.form', $this->refs());
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $employee = Employee::create($validated + ['created_by' => auth()->id()]);
        AuditService::created('HR', $employee);
        return redirect()->route('employees.index')->with('success', 'Karyawan ditambahkan.');
    }

    public function show(Employee $employee)
    {
        return view('hr.employee.show', ['employee' => $employee->load(['company', 'site', 'division', 'department', 'shift', 'attendances'])]);
    }

    public function edit(Employee $employee)
    {
        return view('hr.employee.form', ['employee' => $employee] + $this->refs());
    }

    public function update(Request $request, Employee $employee)
    {
        $old = $employee->toArray();
        $validated = $this->validateInput($employee->id);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $employee->update($validated + ['updated_by' => auth()->id()]);
        AuditService::updated('HR', $employee, $old);
        return redirect()->route('employees.index')->with('success', 'Karyawan diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        AuditService::deleted('HR', $employee);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Karyawan dihapus.');
    }

    protected function validateInput($id = null): array
    {
        $uniqueCode = $id ? 'unique:employees,code,' . $id : 'unique:employees,code';
        return request()->validate([
            'code' => ['required', 'max:30', $uniqueCode],
            'name' => 'required|max:150',
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'division_id' => 'nullable|exists:divisions,id',
            'department_id' => 'nullable|exists:departments,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'position' => 'nullable|max:100',
            'employment_type' => 'required|in:PERMANENT,CONTRACT,PROBATION,DAILY,OUTSOURCED',
            'npwp' => 'nullable|max:50',
            'nik' => 'nullable|max:30',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:L,P',
            'address' => 'nullable',
            'phone' => 'nullable|max:30',
            'bank_name' => 'nullable|max:100',
            'bank_account' => 'nullable|max:50',
            'basic_salary' => 'required|numeric|min:0',
            'shift_id' => 'nullable|exists:shifts,id',
            'join_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:ACTIVE,INACTIVE,TERMINATED',
        ]);
    }

    protected function refs(): array
    {
        return [
            'employee' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'divisions' => Division::pluck('name', 'id')->all(),
            'departments' => Department::pluck('name', 'id')->all(),
            'shifts' => Shift::pluck('name', 'id')->all(),
            'costCenters' => CostCenter::pluck('name', 'id')->all(),
        ];
    }
}
