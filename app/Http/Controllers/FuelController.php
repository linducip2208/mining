<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FuelTank;
use App\Models\Site;
use App\Services\FuelService;
use Illuminate\Http\Request;

class FuelController extends Controller
{
    public function dashboard(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $siteId = $request->integer('site_id') ?: null;

        $tanks = FuelTank::with('site')
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->where('status', true)->get()
            ->map(function ($t) {
                $t->balance = FuelService::tankBalance($t->id);
                $t->avg_cost = FuelService::tankAvgCost($t->id);
                $t->fill_pct = $t->capacity_liter > 0 ? round($t->balance / $t->capacity_liter * 100, 1) : 0;
                return $t;
            });

        $issues = FuelService::consumptionReport(
            $request->company_id ? (int) $request->company_id : null,
            $siteId, $from, $to
        );

        return view('fuel.dashboard', [
            'tanks' => $tanks,
            'issues' => $issues,
            'totalLiter' => round($issues->sum('liter'), 2),
            'totalCost' => round($issues->sum('total_cost'), 2),
            'from' => $from, 'to' => $to,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function stock(Request $request)
    {
        $tanks = FuelTank::with('site')
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->where('status', true)->get()
            ->map(function ($t) {
                $t->balance = FuelService::tankBalance($t->id);
                $t->avg_cost = FuelService::tankAvgCost($t->id);
                $t->value = round($t->balance * $t->avg_cost, 2);
                return $t;
            });
        return view('fuel.stock', ['tanks' => $tanks, 'sites' => Site::pluck('name', 'id')->all()]);
    }

    public function consumption(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $issues = FuelService::consumptionReport(
            $request->company_id ? (int) $request->company_id : null,
            $request->site_id ? (int) $request->site_id : null,
            $from, $to
        );
        return view('fuel.consumption', [
            'issues' => $issues,
            'from' => $from, 'to' => $to,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function variance(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $issues = FuelService::consumptionReport(null, null, $from, $to)
            ->filter(fn ($i) => in_array($i->variance_status, ['WARNING', 'CRITICAL']));
        return view('fuel.variance', ['issues' => $issues, 'from' => $from, 'to' => $to]);
    }
}
