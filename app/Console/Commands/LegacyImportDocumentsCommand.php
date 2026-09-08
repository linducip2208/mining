<?php

namespace App\Console\Commands;

class LegacyImportDocumentsCommand extends LegacyImportBase
{
    protected $signature = 'legacy:import-documents {--batch=} {--dry-run}';

    protected $description = 'Import BFJ letter/invoice/receipt sheets (same engine)';
}
