<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\MiningOtherCost;
use App\Models\Pit;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\CostEngine;
use Illuminate\Http\Request;

class CostController extends Controller
{
    use AppliesDataScope;

    public function dashboard(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $companyId = $request->integer('company_id') ?: null;
        $siteId = $request->integer('site_id') ?: null;

        // enforce scope on filters
        if ($companyId) {
            $this->ensureCompanyInScope($companyId);
        }
        if ($siteId) {
            $this->ensureSiteInScope($siteId);
        }

        $data = CostEngine::compute($companyId, $siteId, $from, $to);
        return view('cost.dashboard', $data + [
            'from' => $from, 'to' => $to,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function others(Request $request)
    {
        $items = MiningOtherCost::with(['site', 'pit'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->period, fn ($q) => $q->where('period', $request->period))
            ->orderByDesc('period')->paginate(20)->withQueryString();
        return view('cost.others.index', [
            'items' => $items,
            'statuses' => ['DRAFT', 'APPROVED', 'POSTED'],
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'pits' => Pit::all(),
            'components' => ['CONTRACTOR' => 'Kontraktor', 'ROYALTY' => 'Royalti', 'OVERHEAD' => 'Overhead', 'HAULING' => 'Hauling', 'CRUSHER' => 'Crusher', 'OTHER' => 'Lainnya'],
        ]);
    }

    public function storeOther(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'pit_id' => 'nullable|exists:pits,id',
            'period' => 'required|date_format:Y-m',
            'component' => 'required|in:CONTRACTOR,ROYALTY,OVERHEAD,HAULING,CRUSHER,OTHER',
            'description' => 'nullable|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $cost = MiningOtherCost::create($validated + ['status' => 'DRAFT', 'created_by' => auth()->id()]);
        AuditService::created('COST', $cost);
        return back()->with('success', 'Biaya manual tersimpan.');
    }

    public function approveOther(MiningOtherCost $other_cost)
    {
        if (!auth()->user()->hasPermission('cost.approve')) {
            abort(403);
        }
        $other_cost->update(['status' => 'APPROVED']);
        AuditService::log('APPROVE', 'COST', $other_cost->id, MiningOtherCost::class);
        return back()->with('success', 'Biaya disetujui.');
    }

    public function postOther(MiningOtherCost $other_cost)
    {
        if (!auth()->user()->hasPermission('cost.post')) {
            abort(403);
        }
        try {
            CostEngine::postOtherCost($other_cost);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Biaya diposting ke jurnal.');
    }
}
