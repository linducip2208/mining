<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\StockLedger;
use App\Models\Warehouse;
use Illuminate\Console\Command;

/**
 * PART 42 — legacy stock reconciliation. Never trusts the imported stock
 * card: recomputes ERP balance from stock_ledger and compares against the
 * legacy opening imported per item+warehouse.
 */
class InventoryReconcileLegacy extends Command
{
    protected $signature = 'inventory:reconcile-legacy {--warehouse= : Filter by warehouse code} {--csv= : Export path}';

    protected $description = 'Rekonsiliasi stok legacy: saldo akhir legacy (opening import) vs saldo ERP dari StockLedger';

    public function handle(): int
    {
        $query = StockLedger::query()
            ->join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_ledger.warehouse_id')
            ->selectRaw("
                items.code as item_code, items.name as item_name, warehouses.code as wh_code,
                COALESCE(SUM(CASE WHEN stock_ledger.source = 'LEGACY_IMPORT' AND stock_ledger.movement_type = 'OPENING' THEN stock_ledger.qty_in ELSE 0 END),0) as legacy_opening,
                COALESCE(SUM(stock_ledger.qty_in),0) - COALESCE(SUM(stock_ledger.qty_out),0) as erp_balance,
                COALESCE(SUM(CASE WHEN stock_ledger.source = 'LEGACY_IMPORT' AND stock_ledger.movement_type = 'OPENING' THEN stock_ledger.qty_in ELSE 0 END),0) - (COALESCE(SUM(stock_ledger.qty_in),0) - COALESCE(SUM(stock_ledger.qty_out),0)) as variance,
                COALESCE(SUM(CASE WHEN stock_ledger.source = 'LEGACY_IMPORT' AND stock_ledger.movement_type = 'OPENING' THEN stock_ledger.total_cost ELSE 0 END),0) as legacy_value,
                COALESCE(items.avg_cost,0) as avg_cost,
                COUNT(CASE WHEN items.id IS NULL THEN 1 END) as missing_item,
                COUNT(CASE WHEN warehouses.id IS NULL THEN 1 END) as missing_wh
            ")
            ->groupBy('items.code', 'items.name', 'warehouses.code', 'items.avg_cost')
            ->orderBy('items.code');

        if ($this->option('warehouse')) {
            $query->where('warehouses.code', $this->option('warehouse'));
        }
        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('Tidak ada data stok legacy (source=LEGACY_IMPORT) untuk direkonsiliasi.');

            return self::SUCCESS;
        }

        $this->line(str_repeat('-', 110));
        $this->line(sprintf('%-14s %-30s %-8s %14s %14s %14s %14s %s', 'ITEM', 'NAMA', 'GUDANG', 'LEGACY AWAL', 'ERP SALDO', 'VARIANS', 'NILAI VARIANS', 'STATUS'));
        $this->line(str_repeat('-', 110));

        $varianceCount = 0;
        $csv = [];
        foreach ($rows as $r) {
            $variance = round((float) $r->legacy_opening - (float) $r->erp_balance, 4);
            $valueVariance = round($variance * (float) $r->avg_cost, 2);
            $status = abs($variance) <= 0.0001 ? 'MATCH' : 'VARIANCE';
            if ($status === 'VARIANCE') {
                // legitimate: ERP operations after the opening import change the balance
                $status = abs($variance) > 0.0001 ? 'VARIANCE' : 'MATCH';
            }
            if ($status === 'VARIANCE') {
                $varianceCount++;
            }
            $this->line(sprintf(
                '%-14s %-30s %-8s %14.2f %14.2f %14.2f %14.2f %s',
                $r->item_code, mb_substr($r->item_name, 0, 30), $r->wh_code,
                $r->legacy_opening, $r->erp_balance, $variance, $valueVariance, $status
            ));
            $csv[] = [$r->item_code, $r->item_name, $r->wh_code, $r->legacy_opening, $r->erp_balance, $variance, $valueVariance, $status];
        }
        $this->line(str_repeat('-', 110));

        if ($this->option('csv')) {
            $path = (string) $this->option('csv');
            $out = fopen($path, 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ITEM', 'NAMA', 'GUDANG', 'LEGACY AWAL', 'ERP SALDO', 'VARIANS', 'NILAI VARIANS', 'STATUS']);
            foreach ($csv as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
            $this->info("CSV ditulis: {$path}");
        }

        $this->info("Rekonsiliasi: {$rows->count()} kombinasi item+gudang, {$varianceCount} variance (wajar bila ada movement ERP setelah opening import).");

        return self::SUCCESS;
    }
}
