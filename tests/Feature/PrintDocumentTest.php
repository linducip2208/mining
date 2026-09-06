<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\DocumentVerificationService;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(CoreSeeder::class);

        return User::where('username', 'superadmin')->firstOrFail();
    }

    public function test_report_has_dedicated_print_view_and_branding(): void
    {
        $admin = $this->admin();
        Setting::create(['key' => 'system.company_name', 'value' => 'PT Audit Print', 'type' => 'text', 'scope_type' => 'GLOBAL', 'scope_id' => null]);
        $response = $this->actingAs($admin)->get('/reports/production/print?from=2026-09-01&to=2026-09-06');
        $response->assertOk()->assertSee('Laporan Produksi')->assertSee('PT Audit Print')->assertSee('Periode');
        $this->assertStringNotContainsString('window.print', $response->getContent());
    }

    public function test_report_pdf_is_server_generated(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin)->get('/reports/production/pdf');
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_verification_token_does_not_expose_internal_id(): void
    {
        $token = DocumentVerificationService::token('invoice', 'INV-2026-0001');
        $this->assertStringNotContainsString('INV-2026-0001', $token);
        $this->assertSame(['type' => 'invoice', 'reference' => 'INV-2026-0001'], DocumentVerificationService::decode($token));
    }
}
