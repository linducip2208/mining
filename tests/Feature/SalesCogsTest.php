<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\Setting;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCogsTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    public function test_delivery_with_zero_avg_cost_blocked_by_policy(): void
    {
        Setting::set('inventory.cogs_zero_cost_policy', 'BLOCK', 'string');
        // stock received with cost 0 → avg_cost stays 0
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 0]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('COGS nol');

        SalesService::completeDelivery($flow['do'], $flow['ticket']);
    }

    public function test_delivery_with_zero_avg_cost_warn_policy_posts_and_audits(): void
    {
        Setting::set('inventory.cogs_zero_cost_policy', 'WARN', 'string');
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 0]);

        SalesService::completeDelivery($flow['do'], $flow['ticket']);

        $flow['do']->refresh();
        $this->assertEquals('COMPLETED', $flow['do']->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'WARNING']);
        $this->assertNull(JournalEntry::where('source_type', 'SALES_COGS')->first());
    }

    public function test_delivery_with_cost_posts_cogs_at_moving_average(): void
    {
        Setting::set('inventory.cogs_zero_cost_policy', 'BLOCK', 'string');
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 10000]);

        SalesService::completeDelivery($flow['do'], $flow['ticket']);

        $journal = JournalEntry::where('source_type', 'SALES_COGS')->where('status', 'POSTED')->firstOrFail();
        $debit = (float) $journal->lines()->where('debit', '>', 0)->sum('debit');
        $this->assertEquals(50000.0, $debit); // net 5 × 10000
    }

    public function test_invoice_idempotency_one_active_per_so(): void
    {
        $flow = $this->buildFlow(['stockQty' => 100, 'stockCost' => 10000]);
        SalesService::completeDelivery($flow['do'], $flow['ticket']);

        SalesService::createInvoice($flow['so'], today());
        $this->expectException(\DomainException::class);
        SalesService::createInvoice($flow['so'], today());
    }
}
