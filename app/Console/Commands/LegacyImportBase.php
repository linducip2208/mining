<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

abstract class LegacyImportBase extends Command
{
    protected string $sheetHint = '';

    public function handle(): int
    {
        $this->info(static::class.' delegates to legacy:import (single Import Engine, no duplicated parsing).');

        return $this->call('legacy:import', [
            '--batch' => $this->option('batch'),
            '--dry-run' => $this->option('dry-run'),
        ]);
    }
}
