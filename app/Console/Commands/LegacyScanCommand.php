<?php

namespace App\Console\Commands;

use App\Services\Bfj\BfjImportEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LegacyScanCommand extends Command
{
    protected $signature = 'legacy:scan {file : stored path (storage/app) or absolute path} {--company=} {--site=} {--warehouse=} {--mode=HISTORY_ONLY}';

    protected $description = 'Scan BFJ workbook into staging without writing ERP data';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $stored = $file;
        if (is_file($file)) {
            $stored = 'bfj-imports/'.basename($file);
            Storage::disk('local')->put($stored, file_get_contents($file));
        }
        $batch = BfjImportEngine::scan($stored, [
            'company_id' => $this->option('company') ?: null,
            'site_id' => $this->option('site') ?: null,
            'warehouse_id' => $this->option('warehouse') ?: null,
            'mode' => $this->option('mode'),
            'user_id' => null,
        ]);
        $this->info("Batch #{$batch->id} scanned: {$batch->sheets->count()} sheets.");

        return self::SUCCESS;
    }
}
