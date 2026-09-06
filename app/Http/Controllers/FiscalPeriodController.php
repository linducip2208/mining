<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FiscalPeriod;
use App\Services\AuditService;
use App\Services\PeriodService;
use Illuminate\Http\Request;

class FiscalPeriodController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->integer('year', now()->year);
        $items = FiscalPeriod::with(['closer'])
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->where('period', 'like', $year . '-%')
            ->orderBy('company_id')->orderBy('period')
            ->paginate(30)->withQueryString();

        return view('finance.period.index', [
            'items' => $items,
            'year' => $year,
            'companies' => Company::pluck('name', 'id')->all(),
        ]);
    }

    public function close(Request $request, FiscalPeriod $fiscal_period)
    {
        if (!auth()->user()->hasPermission('fiscal.close')) {
            abort(403);
        }
        try {
            PeriodService::closePeriod($fiscal_period->company_id, $fiscal_period->period);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Periode ' . $fiscal_period->period . ' ditutup.');
    }

    public function reopen(Request $request, FiscalPeriod $fiscal_period)
    {
        if (!auth()->user()->hasPermission('fiscal.reopen')) {
            abort(403);
        }
        $validated = $request->validate(['reason' => 'required|max:500']);
        try {
            PeriodService::reopenPeriod($fiscal_period->company_id, $fiscal_period->period, $validated['reason']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Periode ' . $fiscal_period->period . ' dibuka kembali.');
    }

    public function closeYear(Request $request)
    {
        if (!auth()->user()->hasPermission('fiscal.close')) {
            abort(403);
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'year' => 'required|integer|min:2000|max:2100',
        ]);
        try {
            $journal = PeriodService::closeYear((int) $validated['company_id'], (int) $validated['year']);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Tutup buku ' . $validated['year'] . ' selesai: ' . $journal->number);
    }

    public function depreciate(Request $request)
    {
        if (!auth()->user()->hasPermission('fiscal.close')) {
            abort(403);
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'period' => 'required|date_format:Y-m',
        ]);
        try {
            $journal = PeriodService::depreciationRun((int) $validated['company_id'], $validated['period']);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Penyusutan ' . $validated['period'] . ' diposting: ' . $journal->number);
    }
}
