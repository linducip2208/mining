<?php

namespace Tests\Feature;

use App\Models\Crusher;
use App\Models\ProductionBatch;
use App\Services\ProductionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionYieldTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    private $raw;

    private $fg;

    private Crusher $crusher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        $this->raw = $this->makeItem('RAW-'.uniqid(), 'RAW');
        $this->fg = $this->makeItem('FG-'.uniqid(), 'PRODUCT');
        StockService::move($this->wh->id, $this->raw->id, 'PURCHASE', 1000, 0, $this->co->id, $this->site->id, null, 'OPENING', 'OP-1', 50000, today()->toDateString());
        $this->crusher = Crusher::create([
            'site_id' => $this->site->id, 'code' => 'CR-1', 'name' => 'Crusher 1',
            'warehouse_id' => $this->wh->id,
        ]);
    }

    private function makeBatch(float $input, float $netOutput): ProductionBatch
    {
        $batch = ProductionBatch::create([
            'number' => 'PRD-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'crusher_id' => $this->crusher->id, 'date' => today(),
            'input_tonnage' => $input, 'net_output' => $netOutput, 'status' => 'APPROVED',
            'created_by' => $this->admin->id,
        ]);
        $batch->inputs()->create(['item_id' => $this->raw->id, 'warehouse_id' => $this->wh->id, 'tonnage' => $input]);
        $batch->outputs()->create(['item_id' => $this->fg->id, 'warehouse_id' => $this->wh->id, 'gross_tonnage' => $netOutput, 'net_tonnage' => $netOutput]);

        return $batch;
    }

    public function test_yield_above_input_rejected(): void
    {
        $batch = $this->makeBatch(100, 400);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('tidak masuk akal');

        ProductionService::post($batch);
    }

    public function test_valid_batch_posts_stock_in_out_and_journal(): void
    {
        $batch = $this->makeBatch(100, 90);

        ProductionService::post($batch);

        $batch->refresh();
        $this->assertSame('POSTED', $batch->status);
        $this->assertSame(900.0, StockService::balance($this->wh->id, $this->raw->id));
        $this->assertSame(90.0, StockService::balance($this->wh->id, $this->fg->id));
        $this->assertDatabaseHas('journal_entries', ['source_type' => 'PRODUCTION', 'status' => 'POSTED', 'source_id' => $batch->id]);
    }

    public function test_double_post_rejected(): void
    {
        $batch = $this->makeBatch(100, 90);
        ProductionService::post($batch);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('sudah diposting');

        ProductionService::post($batch);
    }

    public function test_batch_without_input_rejected(): void
    {
        $batch = ProductionBatch::create([
            'number' => 'PRD-'.uniqid(), 'company_id' => $this->co->id, 'site_id' => $this->site->id,
            'crusher_id' => $this->crusher->id, 'date' => today(),
            'input_tonnage' => 0, 'net_output' => 50, 'status' => 'APPROVED',
            'created_by' => $this->admin->id,
        ]);
        $batch->outputs()->create(['item_id' => $this->fg->id, 'warehouse_id' => $this->wh->id, 'gross_tonnage' => 50, 'net_tonnage' => 50]);

        $this->expectException(\DomainException::class);

        ProductionService::post($batch);
    }
}
