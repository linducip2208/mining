<?php

namespace App\Console\Commands;

class LegacyImportPayrollCommand extends LegacyImportBase
{
    protected $signature = 'legacy:import-payroll {--batch=} {--dry-run}';

    protected $description = 'Import BFJ payroll sheets (same engine)';
}
