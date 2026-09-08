<?php

namespace App\Console\Commands;

use App\Models\AccountingMapping;
use App\Models\CustomerDeposit;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PaymentAllocation;
use App\Models\PayrollRun;
use App\Models\StockLedger;
use App\Models\VendorBill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * PART 44 — accounting integrity audit. Exit code 1 on any critical finding.
 * Validates: Debits = Credits, no unbalanced journal, no orphan journal,
 * reversal reference valid, AR vs invoice allocations, AP vs vendor payments,
 * inventory GL vs stock valuation (warn-level tolerance),
 * payroll payable vs payroll status.
 */
class AccountingAuditIntegrity extends Command
{
    protected $signature = 'accounting:audit-integrity';

    protected $description = 'Audit integritas accounting: jurnal seimbang, AR/AP vs alokasi, payroll payable, reversal valid, GL persediaan vs valuasi stok';

    protected function printDetail(): bool
    {
        return in_array('-v', (array) ($_SERVER['argv'] ?? []), true) || in_array('--verbose', (array) ($_SERVER['argv'] ?? []), true);
    }

    public function handle(): int
    {
        $critical = 0;
        $warnings = 0;

        // 1. unbalanced journals
        $unbalanced = JournalEntry::query()
            ->selectRaw('journal_entries.id, journal_entries.number, COALESCE(SUM(journal_lines.debit),0) d, COALESCE(SUM(journal_lines.credit),0) c')
            ->join('journal_lines', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->groupBy('journal_entries.id', 'journal_entries.number')
            ->havingRaw('ABS(COALESCE(SUM(journal_lines.debit),0) - COALESCE(SUM(journal_lines.credit),0)) > 0.01')
            ->get();
        if ($unbalanced->isNotEmpty()) {
            $critical += $unbalanced->count();
            $this->error("JURNAL TIDAK SEIMBANG: {$unbalanced->count()}.");
            if ($this->printDetail()) {
                foreach ($unbalanced as $j) {
                    $this->line("  #{$j->id} {$j->number}: debit {$j->d} vs credit {$j->c}");
                }
            }
        }

        // 2. journals without lines (orphan)
        $orphanJournals = JournalEntry::whereDoesntHave('lines')->count();
        if ($orphanJournals > 0) {
            $critical += $orphanJournals;
            $this->error("JURNAL TANPA BARIS: {$orphanJournals}.");
        }

        // 3. journal lines without COA (orphan)
        $orphanLines = JournalLine::whereNull('chart_of_account_id')->count();
        if ($orphanLines > 0) {
            $critical += $orphanLines;
            $this->error("BARIS JURNAL TANPA COA: {$orphanLines}.");
        }

        // 4. AR: paid_amount vs allocations (payment rows + deposit allocations)
        $arDrift = 0;
        $invoices = Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])->get();
        foreach ($invoices as $inv) {
            $allocated = (float) PaymentAllocation::where('invoice_type', 'CUSTOMER')->where('invoice_id', $inv->id)->sum('amount');
            $depositUsed = (float) CustomerDeposit::where('movement_type', 'DEPOSIT_USED')
                ->where('ref_type', 'INVOICE')->where('ref_id', $inv->id)->sum('amount');
            if (abs($allocated + $depositUsed - (float) $inv->paid_amount) > 0.01) {
                $arDrift++;
                if ($this->printDetail()) {
                    $this->line("  invoice {$inv->number}: paid_amount {$inv->paid_amount} vs alokasi {$allocated} + deposit {$depositUsed}");
                }
            }
        }
        if ($arDrift > 0) {
            $critical += $arDrift;
            $this->error("AR DRIFT: {$arDrift} invoice dengan paid_amount ≠ alokasi pembayaran.");
        }

        // 5. AP: paid_amount vs vendor payment journals (sourceType VENDOR_PAYMENT)
        $apDrift = 0;
        $bills = VendorBill::whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])->get();
        foreach ($bills as $bill) {
            $paid = (float) DB::table('journal_lines')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_entries.source_type', 'VENDOR_PAYMENT')
                ->where('journal_entries.source_id', $bill->id)
                ->sum('journal_lines.debit');
            if (abs($paid - (float) $bill->paid_amount) > 0.01) {
                $apDrift++;
                if ($this->printDetail()) {
                    $this->line("  bill {$bill->number}: paid_amount {$bill->paid_amount} vs jurnal bayar {$paid}");
                }
            }
        }
        if ($apDrift > 0) {
            $critical += $apDrift;
            $this->error("AP DRIFT: {$apDrift} vendor bill dengan paid_amount ≠ jurnal pembayaran.");
        }

        // 6. payroll: POSTED/PAID runs must have a journal; unposted runs must not
        $payrollIssues = 0;
        foreach (PayrollRun::whereIn('status', ['POSTED', 'PAID'])->get() as $run) {
            if (! $run->journal_entry_id || ! JournalEntry::find($run->journal_entry_id)) {
                $payrollIssues++;
                if ($this->printDetail()) {
                    $this->line("  payroll {$run->number} berstatus {$run->status} tanpa jurnal.");
                }
            }
        }
        foreach (PayrollRun::whereIn('status', ['DRAFT', 'CALCULATED', 'APPROVED'])->get() as $run) {
            if (JournalEntry::where('source_type', 'PAYROLL')->where('source_id', $run->id)->exists()) {
                $payrollIssues++;
                if ($this->printDetail()) {
                    $this->line("  payroll {$run->number} belum POSTED tapi jurnal PAYROLL sudah ada.");
                }
            }
        }
        if ($payrollIssues > 0) {
            $critical += $payrollIssues;
            $this->error("PAYROLL PAYABLE vs STATUS: {$payrollIssues} ketidaksesuaian.");
        }

        // 7. reversal references must point to a real posted journal
        $badReversal = JournalEntry::whereNotNull('reversal_of_id')
            ->whereNotIn('reversal_of_id', JournalEntry::where('status', 'POSTED')->select('id'))
            ->count();
        if ($badReversal > 0) {
            $critical += $badReversal;
            $this->error("REVERSAL REFERENCE TIDAK VALID: {$badReversal}.");
        }

        // 8. inventory GL vs stock valuation (warn-level: legacy opening books may differ)
        $inventoryGl = 0.0;
        foreach (['INVENTORY_FG', 'INVENTORY_RAW', 'INVENTORY_SPAREPART', 'INVENTORY_FUEL', 'INVENTORY_GENERAL'] as $key) {
            $mapping = AccountingMapping::where('code', $key)->first();
            if (! $mapping) {
                continue;
            }
            $inventoryGl += (float) JournalLine::where('chart_of_account_id', $mapping->chart_of_account_id)
                ->whereIn('journal_entry_id', JournalEntry::where('status', 'POSTED')->select('id'))
                ->sum(DB::raw('debit - credit'));
        }
        $stockValue = (float) StockLedger::join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->selectRaw('COALESCE(SUM((stock_ledger.qty_in - stock_ledger.qty_out) * COALESCE(items.avg_cost,0)),0) v')
            ->value('v');
        $gap = round($inventoryGl - $stockValue, 2);
        if (abs($gap) > 1) {
            $warnings++;
            $this->warn(sprintf('INVENTORY GL vs VALUASI STOK: GL %.2f vs stok %.2f (selisih %.2f) — warn, cek opening balance.', $inventoryGl, $stockValue, $gap));
        }

        if ($critical === 0) {
            $this->info('ACCOUNTING AUDIT PASS — jurnal seimbang: '.JournalEntry::count().", AR match, AP match, payroll match. Warning: {$warnings}.");

            return self::SUCCESS;
        }
        $this->error("ACCOUNTING AUDIT FAILED — {$critical} temuan kritis, {$warnings} warning.");

        return self::FAILURE;
    }
}
