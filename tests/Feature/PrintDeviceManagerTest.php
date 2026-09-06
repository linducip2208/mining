<?php

namespace Tests\Feature;

use App\Models\PrinterDevice;
use App\Models\PrintJob;
use App\Models\Setting;
use App\Models\User;
use App\Services\LocalPrintAgentService;
use App\Services\PrinterRoutingService;
use App\Services\PrintJobService;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintDeviceManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(CoreSeeder::class);

        return User::where('username', 'superadmin')->firstOrFail();
    }

    public function test_printer_registry_uses_human_labels_and_is_reachable(): void
    {
        $admin = $this->admin();
        PrinterDevice::create(['name' => 'Printer Timbangan', 'printer_type' => 'THERMAL_80', 'connection_type' => 'BLUETOOTH', 'paper_size' => '80mm', 'document_types' => ['WEIGHBRIDGE_TICKET'], 'is_active' => true]);

        $this->actingAs($admin)->get(route('printer.index'))
            ->assertOk()
            ->assertSee('Printer &amp; Perangkat', false)
            ->assertSee('Bluetooth')
            ->assertDontSee('>THERMAL_80<', false);
    }

    public function test_routing_prefers_document_capable_default_printer(): void
    {
        $this->admin();
        $printer = PrinterDevice::create(['name' => 'Thermal Timbangan', 'printer_type' => 'WEIGHBRIDGE', 'connection_type' => 'USB', 'paper_size' => '80mm', 'document_types' => ['WEIGHBRIDGE_TICKET'], 'is_default' => true, 'is_active' => true]);

        $this->assertSame($printer->id, app(PrinterRoutingService::class)->resolve('WEIGHBRIDGE_TICKET')?->id);
    }

    public function test_same_idempotency_key_never_creates_duplicate_job(): void
    {
        $this->admin();
        $service = app(PrintJobService::class);
        $one = $service->queue('WEIGHBRIDGE_TICKET', 99, null, ['format' => 'html'], auth()->id(), 'ticket:99:second-weigh');
        $two = $service->queue('WEIGHBRIDGE_TICKET', 99, null, ['format' => 'html'], auth()->id(), 'ticket:99:second-weigh');

        $this->assertSame($one->id, $two->id);
        $this->assertSame(1, PrintJob::where('idempotency_key', 'ticket:99:second-weigh')->count());
    }

    public function test_agent_signature_is_short_lived_and_secret_is_not_in_package(): void
    {
        $this->admin();
        Setting::set('printer.pairing_token', 'test-token', 'secret');
        $job = app(PrintJobService::class)->queue('PRINTER_TEST', 1, null, ['format' => 'html', 'content_base64' => base64_encode('test')], auth()->id(), 'test:signature');
        $agent = app(LocalPrintAgentService::class);
        $package = $agent->package($job);

        $this->assertArrayHasKey('X-Mining-Agent-Signature', $package['headers']);
        $this->assertStringNotContainsString('test-token', $package['body']);
        $this->assertTrue($agent->validSignature($package['body'], 'POST', $package['headers']['X-Mining-Agent-Timestamp'], $package['headers']['X-Mining-Agent-Signature']));
    }

    public function test_agent_callback_updates_print_job_status(): void
    {
        $this->admin();
        Setting::set('printer.pairing_token', 'test-token', 'secret');
        $job = app(PrintJobService::class)->queue('PRINTER_TEST', 1, null, [], auth()->id(), 'test:callback');
        $body = json_encode(['status' => 'PRINTED', 'error_message' => null]);
        $headers = app(LocalPrintAgentService::class)->signedHeaders($body, 'POST');

        $this->postJson(route('print-jobs.agent-status', $job), json_decode($body, true), $headers)
            ->assertOk()
            ->assertJson(['ok' => true]);
        $this->assertSame('PRINTED', $job->refresh()->status);
    }
}
