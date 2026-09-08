<?php

namespace App\Console\Commands;

use App\Models\LegacyImportBatch;
use App\Services\Bfj\BfjImportEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LegacyImportCommand extends Command
{
    protected $signature = 'legacy:import {--profile=BFJ_LEGACY_2026} {--file=} {--batch=} {--dry-run} {--force}';

    protected $description = 'Import BFJ legacy batch (use --dry-run for impact simulation, no writes)';

    public function handle(): int
    {
        $batch = null;
        if ($this->option('batch')) {
            $batch = LegacyImportBatch::findOrFail($this->option('batch'));
        } elseif ($this->option('file')) {
            $file = str_replace('\\', '/', (string) $this->option('file'));
            $stored = $file;
            if (is_file($file)) {
                $stored = 'bfj-imports/'.basename($file);
                Storage::disk('local')->put($stored, file_get_contents($file));
            } elseif (is_file(base_path($file))) {
                $stored = 'bfj-imports/'.basename($file);
                Storage::disk('local')->put($stored, file_get_contents(base_path($file)));
            }
            $batch = BfjImportEngine::scan($stored, []);
        }
        abort_unless($batch, 1, 'Provide --batch or --file');
        $batch->load('sheets');

        if ($this->option('dry-run')) {
            $impact = BfjImportEngine::impact($batch);
            $this->table(['Metric', 'Value'], collect($impact)->map(fn ($v, $k) => [$k, is_bool($v) ? ($v ? 'yes' : 'no') : $v])->values()->all());
            $this->info('Dry run — no writes.');

            return self::SUCCESS;
        }
        $result = BfjImportEngine::import($batch, (bool) $this->option('force'));
        $this->info("Imported {$result['imported']}, skipped {$result['skipped']}.");

        return self::SUCCESS;
    }
}
