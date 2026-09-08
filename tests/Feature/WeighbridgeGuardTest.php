<?php

namespace Tests\Feature;

use App\Models\Weighbridge;
use App\Models\WeighbridgeTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeighbridgeGuardTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    private function ticket(string $status, float $net = 5): WeighbridgeTicket
    {
        $wb = Weighbridge::create(['site_id' => $this->site->id, 'code' => 'WB-'.uniqid(), 'name' => 'WB Test']);

        return WeighbridgeTicket::create([
            'ticket_no' => 'WB-T-'.uniqid(), 'weighbridge_id' => $wb->id, 'company_id' => $this->co->id,
            'site_id' => $this->site->id, 'direction' => 'OUT',
            'first_weight' => 1000 + $net, 'second_weight' => 1000,
            'gross' => $net, 'tare' => 0, 'net' => $net,
            'status' => $status, 'created_by' => $this->admin->id,
        ]);
    }

    public function test_override_on_posted_ticket_rejected(): void
    {
        $ticket = $this->ticket('POSTED');

        $this->post(route('weighbridge.override', $ticket), [
            'gross' => 10, 'tare' => 2, 'override_reason' => 'attempt',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame(5.0, (float) $ticket->net);
        $this->assertNull($ticket->override_reason);
    }

    public function test_override_to_zero_net_rejected(): void
    {
        $ticket = $this->ticket('COMPLETE');

        $this->post(route('weighbridge.override', $ticket), [
            'gross' => 10, 'tare' => 10, 'override_reason' => 'zero out',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame(5.0, (float) $ticket->net);
    }

    public function test_valid_override_on_complete_ticket_records_before_after(): void
    {
        $ticket = $this->ticket('COMPLETE');

        $this->post(route('weighbridge.override', $ticket), [
            'gross' => 12, 'tare' => 3, 'override_reason' => 'calibration fix',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame(9.0, (float) $ticket->net);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'WEIGHBRIDGE', 'action' => 'OVERRIDE', 'record_id' => $ticket->id,
        ]);
    }

    public function test_post_rejects_zero_net_ticket(): void
    {
        $ticket = $this->ticket('COMPLETE', 0);
        $ticket->update(['net' => 0]);

        $this->post(route('weighbridge.post', $ticket))->assertRedirect();

        $this->assertSame('COMPLETE', $ticket->fresh()->status);
    }

    public function test_second_post_is_idempotent_noop(): void
    {
        $ticket = $this->ticket('POSTED');

        $this->post(route('weighbridge.post', $ticket))->assertRedirect();

        $this->assertSame('POSTED', $ticket->fresh()->status);
    }
}
