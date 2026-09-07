<?php

namespace App\Console\Commands;

use App\Services\MaintenanceService;
use Illuminate\Console\Command;

class GenerateDueWorkOrders extends Command
{
    protected $signature = 'maintenance:generate-wo';

    protected $description = 'Buat WO draft dari jadwal pemeliharaan yang jatuh tempo';

    public function handle(): int
    {
        $count = MaintenanceService::generateDueWorkOrders();
        $this->info("WO draft dibuat: {$count}");

        return self::SUCCESS;
    }
}
