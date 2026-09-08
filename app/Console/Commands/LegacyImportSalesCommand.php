<?php

namespace App\Console\Commands;

class LegacyImportSalesCommand extends LegacyImportBase
{
    protected $signature = 'legacy:import-sales {--batch=} {--dry-run}';

    protected $description = 'Import BFJ sales sheets (same engine)';
}
