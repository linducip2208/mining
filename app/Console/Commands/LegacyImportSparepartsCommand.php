<?php

namespace App\Console\Commands;

class LegacyImportSparepartsCommand extends LegacyImportBase
{
    protected $signature = 'legacy:import-spareparts {--batch=} {--dry-run}';

    protected $description = 'Import BFJ sparepart sheets (same engine)';
}
