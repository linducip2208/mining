<?php

namespace App\Console\Commands;

use App\Services\PeriodService;
use Illuminate\Console\Command;

class DepreciateAssets extends Command
{
    protected $signature = 'accounting:depreciate {--period= : periode YYYY-MM (default bulan berjalan)} {--company= : ID perusahaan (default semua)}';

    protected $description = 'Posting penyusutan aset tetap bulanan (straight-line, idempotent per periode)';

    public function handle(): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->error('Format periode harus YYYY-MM.');
            return self::FAILURE;
        }
        $companies = $this->option('company')
            ? \App\Models\Company::whereKey($this->option('company'))->get()
            : \App\Models\Company::all();

        foreach ($companies as $company) {
            try {
                $journal = PeriodService::depreciationRun($company->id, $period);
                $this->info("{$company->code} {$period}: {$journal->number} (Rp " . number_format($journal->total_debit, 0) . ')');
            } catch (\DomainException|\InvalidArgumentException $e) {
                $this->warn("{$company->code} {$period}: {$e->getMessage()}");
            }
        }
        return self::SUCCESS;
    }
}
