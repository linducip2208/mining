<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\HsePermit;
use App\Models\HseReport;
use App\Models\User;
use App\Notifications\SystemAlert;
use App\Services\HseService;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Company $co;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);
        $this->actingAs(User::where('username', 'superadmin')->first());
        $this->co = Company::create(['code' => 'TST', 'name' => 'Test Co', 'status' => true]);
        $this->employee = Employee::create([
            'company_id' => $this->co->id, 'code' => 'EMP-'.uniqid(),
            'name' => 'Test Worker', 'status' => 'ACTIVE',
        ]);
    }

    private function report(array $over = []): HseReport
    {
        return HseService::report([
            'company_id' => $this->co->id,
            'kind' => $over['kind'] ?? 'INCIDENT',
            'occurred_at' => $over['occurred_at'] ?? now(),
            'severity' => $over['severity'] ?? 'LOW',
            'description' => $over['description'] ?? 'Test incident',
            'location' => 'Pit A',
        ]);
    }

    public function test_report_created_with_number_and_open_status(): void
    {
        $report = $this->report();

        $this->assertSame('OPEN', $report->status);
        $this->assertStringStartsWith('HSE', $report->number);
    }

    public function test_high_severity_report_notifies_safety_roles(): void
    {
        Notification::fake();

        $this->report(['severity' => 'CRITICAL']);

        Notification::assertSentTo(
            User::whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN']))->get(),
            SystemAlert::class
        );
    }

    public function test_low_severity_report_does_not_notify(): void
    {
        Notification::fake();

        $this->report(['severity' => 'LOW']);

        Notification::assertNothingSent();
    }

    public function test_investigation_moves_report_to_in_progress(): void
    {
        $report = $this->report();

        HseService::investigate($report, 'Forklift blind spot', 'operator error');

        $report->refresh();
        $this->assertSame('IN_PROGRESS', $report->status);
        $this->assertSame('Forklift blind spot', $report->root_cause);
    }

    public function test_empty_root_cause_rejected(): void
    {
        $report = $this->report();

        $this->expectException(\DomainException::class);

        HseService::investigate($report, '   ');
    }

    public function test_action_without_responsible_or_due_date_rejected(): void
    {
        $report = $this->report();

        $this->expectException(\DomainException::class);

        HseService::addAction($report->id, ['action' => 'Fix']);
    }

    public function test_report_cannot_close_while_action_open(): void
    {
        $report = $this->report();
        $action = HseService::addAction($report->id, [
            'action' => 'Install mirror', 'responsible_id' => $this->employee->id, 'due_date' => today()->addWeek()->toDateString(),
        ]);
        $report->refresh();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('belum selesai');

        HseService::closeReport($report);
    }

    public function test_done_action_must_be_verified_before_case_closure(): void
    {
        $report = $this->report();
        $action = HseService::addAction($report->id, [
            'action' => 'Install mirror', 'responsible_id' => $this->employee->id, 'due_date' => today()->addWeek()->toDateString(),
        ]);
        HseService::closeAction($action, 'Mirror installed, photo attached');
        $report->refresh();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('belum diverifikasi');

        HseService::closeReport($report);
    }

    public function test_full_lifecycle_to_closed(): void
    {
        $report = $this->report();
        $action = HseService::addAction($report->id, [
            'action' => 'Install mirror', 'responsible_id' => $this->employee->id, 'due_date' => today()->addWeek()->toDateString(),
        ]);
        HseService::closeAction($action, 'Mirror installed');
        HseService::verifyAction($action);
        HseService::closeReport($report);

        $report->refresh();
        $action->refresh();
        $this->assertSame('CLOSED', $report->status);
        $this->assertSame('VERIFIED', $action->status);
    }

    public function test_verify_only_done_actions(): void
    {
        $report = $this->report();
        $action = HseService::addAction($report->id, [
            'action' => 'Install mirror', 'responsible_id' => $this->employee->id, 'due_date' => today()->addWeek()->toDateString(),
        ]);

        $this->expectException(\DomainException::class);

        HseService::verifyAction($action);
    }

    public function test_expiring_permit_detected_in_dashboard(): void
    {
        HsePermit::create([
            'company_id' => $this->co->id, 'number' => 'WP-'.uniqid(),
            'work_type' => 'Hot work', 'status' => 'ACTIVE',
            'valid_from' => today()->subDay(), 'valid_until' => today()->addDays(5),
        ]);

        $dash = HseService::dashboard($this->co->id, null, now()->subMonth()->toDateString(), now()->toDateString());

        $this->assertSame(1, $dash['permits_expiring']);
    }
}
