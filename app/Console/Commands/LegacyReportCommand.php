<?php

namespace App\Console\Commands;

use App\Models\LegacyImportBatch;
use App\Services\Bfj\BfjImportEngine;
use Illuminate\Console\Command;

class LegacyReportCommand extends Command
{
    protected $signature = 'legacy:report {batch : batch id}';

    protected $description = 'Print BFJ migration batch report';

    public function handle(): int
    {
        $batch = LegacyImportBatch::with(['sheets', 'matches'])->findOrFail($this->argument('batch'));
        $report = BfjImportEngine::report($batch);
        $this->table(['Metric', 'Value'], collect($report)->map(fn ($v, $k) => [$k, is_array($v) ? json_encode($v) : $v])->values()->all());

        return self::SUCCESS;
    }
}
