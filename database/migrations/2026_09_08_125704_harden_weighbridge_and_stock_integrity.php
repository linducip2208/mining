<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Integrity hardening:
     * - one weighbridge ticket may only ever feed one delivery order
     * - one vendor bill per supplier per supplier-invoice number
     * - deposit ledger: signed direction walk + money vs material-credit kind
     */
    public function up(): void
    {
        $this->addIndexSafely('delivery_orders', function (Blueprint $table) {
            $table->unique('weighbridge_ticket_id', 'delivery_orders_weighbridge_ticket_id_unique');
        });

        $this->addIndexSafely('vendor_bills', function (Blueprint $table) {
            $table->unique(['supplier_id', 'supplier_invoice_no'], 'vendor_bills_supplier_invoice_unique');
        });

        Schema::table('customer_deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_deposits', 'direction')) {
                $table->string('direction', 10)->nullable()->after('movement_type')->comment('PLUS, MINUS');
            }
            if (! Schema::hasColumn('customer_deposits', 'kind')) {
                $table->string('kind', 20)->default('MONEY')->after('direction')->comment('MONEY, MATERIAL_CREDIT');
            }
        });

        // backfill direction from movement type for existing rows
        if (Schema::hasColumn('customer_deposits', 'direction')) {
            DB::table('customer_deposits')
                ->whereNull('direction')
                ->update([
                    'direction' => DB::raw("CASE WHEN movement_type = 'DEPOSIT_IN' THEN 'PLUS' ELSE 'MINUS' END"),
                ]);
        }
    }

    public function down(): void
    {
        $this->dropIndexSafely('delivery_orders', 'delivery_orders_weighbridge_ticket_id_unique');
        $this->dropIndexSafely('vendor_bills', 'vendor_bills_supplier_invoice_unique');

        Schema::table('customer_deposits', function (Blueprint $table) {
            foreach (['direction', 'kind'] as $col) {
                if (Schema::hasColumn('customer_deposits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function addIndexSafely(string $table, Closure $definition): void
    {
        try {
            Schema::table($table, $definition);
        } catch (Throwable) {
            // index already exists (MySQL raises ER_DUP_KEYNAME); treat as applied
        }
    }

    private function dropIndexSafely(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropUnique($name);
            });
        } catch (Throwable) {
            // index absent — nothing to drop
        }
    }
};
