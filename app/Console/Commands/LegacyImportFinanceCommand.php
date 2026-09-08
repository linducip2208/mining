<?php

namespace App\Console\Commands;

class LegacyImportFinanceCommand extends LegacyImportBase
{
    protected $signature = 'legacy:import-finance {--batch=} {--dry-run}';

    protected $description = 'Import BFJ finance sheets (same engine)';
}
