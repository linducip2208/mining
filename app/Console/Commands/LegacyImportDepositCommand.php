<?php

namespace App\Console\Commands;

class LegacyImportDepositCommand extends LegacyImportBase
{
    protected $signature = 'legacy:import-deposit {--batch=} {--dry-run}';

    protected $description = 'Import BFJ deposit sheets (same engine)';
}
