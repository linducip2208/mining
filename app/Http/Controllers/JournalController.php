<?php

namespace App\Http\Controllers;

use App\Models\AccountingMapping;
use App\Models\ChartOfAccount;
use App\Models\CashAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\AccountingService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $items = JournalEntry::with(['lines.chartOfAccount', 'creator'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%")->orWhere('source_number', 'like', "%{$request->q}%"))
            ->when($request->from, fn ($q) => $q->whereDate('journal_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('journal_date', '<=', $request->to))
            ->orderByDesc('journal_date')->paginate(20)->withQueryString();
        return view('finance.journal.index', ['items' => $items, 'journal' => null, 'statuses' => ['DRAFT', 'POSTED', 'VOID']]);
    }

    public function create()
    {
        return view('finance.journal.form', [
            'journal' => null,
            'coas' => ChartOfAccount::where('is_postable', true)->where('status', true)->orderBy('code')->get(),
            'companies' => \App\Models\Company::pluck('name', 'id')->all(),
            'sites' => \App\Models\Site::pluck('name', 'id')->all(),
            'costCenters' => \App\Models\CostCenter::pluck('name', 'id')->all(),
        ]);
    }

    /**
     * Manual journal — validation: debit = credit enforced by AccountingService.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'journal_date' => 'required|date',
            'memo' => 'required|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|max:255',
        ]);

        try {
            $lines = collect($validated['lines'])->map(function ($l) {
                $coa = ChartOfAccount::find($l['chart_of_account_id']);
                return [
                    'code' => $coa->code,
                    'debit' => (float) ($l['debit'] ?? 0),
                    'credit' => (float) ($l['credit'] ?? 0),
                    'memo' => $l['memo'] ?? null,
                ];
            })->all();

            $entry = AccountingService::post((int) $validated['company_id'], $validated['journal_date'], $lines, 'MANUAL', null, null, $validated['memo']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('journals.show', $entry)->with('success', 'Jurnal diposting: ' . $entry->number);
    }

    public function show(JournalEntry $journal)
    {
        return view('finance.journal.index', ['journal' => $journal->load(['lines.chartOfAccount', 'creator']), 'items' => JournalEntry::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'POSTED', 'VOID']]);
    }

    public function reverse(Request $request, JournalEntry $journal)
    {
        $validated = $request->validate(['reason' => 'required|max:500']);
        try {
            $rev = AccountingService::reverse($journal, $validated['reason']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Jurnal di-reverse: ' . $rev->number);
    }
}
