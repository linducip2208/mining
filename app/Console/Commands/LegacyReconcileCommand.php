<?php

namespace App\Console\Commands;

use App\Models\LegacyImportBatch;
use App\Services\Bfj\BfjReconciler;
use Illuminate\Console\Command;

class LegacyReconcileCommand extends Command
{
    protected $signature = 'legacy:reconcile {batch : batch id}';

    protected $description = 'Reconcile staged detail vs legacy recap benchmarks';

    public function handle(): int
    {
        $batch = LegacyImportBatch::findOrFail($this->argument('batch'));
        $results = BfjReconciler::reconcile($batch);
        $this->info('Reconciliation entries: '.count($results));

        return self::SUCCESS;
    }
}
