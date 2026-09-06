<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Company;
use App\Models\Role;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowController extends Controller
{
    public function index()
    {
        return view('settings.approval-workflow', [
            'workflows' => ApprovalWorkflow::with('steps')->orderBy('module')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:approval_workflows,code',
            'name' => 'required|string|max:150',
            'module' => 'required|string|max:50',
            'transaction_type' => 'required|string|max:50',
            'company_id' => 'nullable|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:100',
            'steps.*.role_id' => 'nullable|exists:roles,id',
            'steps.*.min_amount' => 'required|numeric|min:0',
            'steps.*.max_amount' => 'nullable|numeric|min:0',
        ], [], [
            'code' => 'Kode Workflow', 'name' => 'Nama Workflow', 'module' => 'Modul',
            'transaction_type' => 'Jenis Dokumen', 'company_id' => 'Perusahaan', 'site_id' => 'Site',
        ]);

        DB::transaction(function () use ($data): void {
            $workflow = ApprovalWorkflow::create([
                'code' => $data['code'], 'name' => $data['name'], 'module' => strtoupper($data['module']),
                'transaction_type' => strtoupper($data['transaction_type']), 'company_id' => $data['company_id'] ?? null,
                'site_id' => $data['site_id'] ?? null, 'is_active' => true, 'created_by' => auth()->id(),
            ]);
            foreach ($data['steps'] as $sequence => $step) {
                ApprovalStep::create([
                    'approval_workflow_id' => $workflow->id, 'sequence' => $sequence + 1,
                    'name' => $step['name'], 'role_id' => $step['role_id'] ?? null,
                    'min_amount' => $step['min_amount'], 'max_amount' => $step['max_amount'] ?? null,
                    'mode' => 'SEQUENCE',
                ]);
            }
        });

        return back()->with('success', 'Workflow persetujuan berhasil ditambahkan.');
    }

    public function toggle(ApprovalWorkflow $workflow)
    {
        $workflow->update(['is_active' => ! $workflow->is_active, 'updated_by' => auth()->id()]);

        return back()->with('success', 'Status workflow diperbarui.');
    }
}
