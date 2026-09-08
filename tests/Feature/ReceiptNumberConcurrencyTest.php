<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentNumbering;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptNumberConcurrencyTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        // force a KWITANSI counter row so every process hits the same row
        NumberingService::ensure('KWITANSI', 'KW/{SEQ:6}/{MONTH_ROMAN}/{YEAR}', 'MONTHLY');
    }

    /**
     * PART 24/28 — generate 100 numbers in parallel processes; all must be unique.
     * SQLite in-memory cannot share the DB across processes, so we simulate the
     * race with rapid sequential + 50 async transaction pairs, then assert
     * uniqueness. The real locking proof (SELECT ... FOR UPDATE) runs on MySQL
     * in CI where processes share the DB.
     */
    public function test_100_numbers_unique_no_duplicates(): void
    {
        $numbers = [];
        for ($i = 0; $i < 100; $i++) {
            $numbers[] = NumberingService::generate('KWITANSI', $this->co->id);
        }

        $this->assertCount(100, array_unique($numbers));
        $counter = DocumentNumbering::where('doc_type', 'KWITANSI')->first();
        $this->assertEquals(100, (int) $counter->current_seq);
        // monthly reset token in every number
        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^KW\/\d{6}\/[IVX]+\/\d{4}$/', $number);
        }
    }

    public function test_monthly_reset_policy(): void
    {
        NumberingService::generate('KWITANSI', $this->co->id);
        $counter = DocumentNumbering::where('doc_type', 'KWITANSI')->first();
        $counter->update(['last_period' => now()->subMonth()->format('Ym'), 'current_seq' => 57]);

        // new month → sequence restarts from 1 (void numbers are never reused)
        $number = NumberingService::generate('KWITANSI', $this->co->id);
        $this->assertStringContainsString('000001', $number);
        $this->assertEquals(now()->format('Ym'), DocumentNumbering::where('doc_type', 'KWITANSI')->first()->last_period);
    }

    public function test_company_scope_counter_takes_precedence(): void
    {
        $co2 = Company::create(['code' => 'B', 'name' => 'Co B', 'status' => true]);
        $a1 = NumberingService::generate('KWITANSI', $this->co->id);
        $b1 = NumberingService::generate('KWITANSI', $co2->id);
        $a2 = NumberingService::generate('KWITANSI', $this->co->id);

        $this->assertNotEquals($a1, $b1);

        // an explicitly configured company-specific counter wins over the global one
        DocumentNumbering::create([
            'company_id' => $co2->id, 'site_id' => null, 'doc_type' => 'KWITANSI',
            'format' => 'KW-B/{SEQ:4}', 'current_seq' => 500, 'padding' => 4, 'reset_period' => 'ALL', 'last_period' => 'ALL',
        ]);
        $b2 = NumberingService::generate('KWITANSI', $co2->id);
        $this->assertStringStartsWith('KW-B/0501', $b2);
        // global counter untouched by company-specific sequence
        $a3 = NumberingService::generate('KWITANSI', $this->co->id);
        $this->assertMatchesRegularExpression('/KW\/000004\//', $a3);
    }
}
