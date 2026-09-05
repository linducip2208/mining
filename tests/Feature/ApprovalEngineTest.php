<?php

namespace Tests\Feature;

use App\Models\ApprovalWorkflow;
use App\Models\Company;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $requester;
    protected User $approver;
    protected User $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CoreSeeder::class);
        $this->company = Company::create(['code' => 'APT', 'name' => 'Approval Test Co', 'status' => true]);

        $this->requester = $this->makeUser('appr_req', 'PURCHASING');
        $this->approver = $this->makeUser('appr_mgr', 'SITE_MANAGER');
        $this->outsider = $this->makeUser('appr_out', 'SALES');

        $wf = ApprovalWorkflow::create([
            'code' => 'TEST-PR', 'name' => 'Test PR Approval',
            'module' => 'PROCUREMENT', 'transaction_type' => 'PURCHASE_REQUEST',
            'is_active' => true, 'created_by' => 1,
        ]);
        $wf->steps()->create([
            'sequence' => 1, 'name' => 'Manager Approval',
            'role_id' => Role::where('code', 'SITE_MANAGER')->first()->id,
            'min_amount' => 0, 'max_amount' => null, 'mode' => 'SEQUENCE',
        ]);
    }

    protected function makeUser(string $username, string $roleCode): User
    {
        $user = User::create([
            'name' => $username, 'username' => $username, 'email' => $username . '@test.local',
            'password' => bcrypt('Test!2345'), 'status' => 'ACTIVE',
        ]);
        $user->roles()->attach(Role::where('code', $roleCode)->first()->id, [
            'scope' => 'ALL_COMPANIES', 'company_id' => null, 'site_id' => null,
        ]);
        return $user;
    }

    protected function makePR(): PurchaseRequest
    {
        return PurchaseRequest::create([
            'number' => 'PR-TEST-' . uniqid(),
            'company_id' => $this->company->id,
            'request_date' => today(),
            'status' => 'DRAFT',
            'created_by' => $this->requester->id,
        ]);
    }

    public function test_submit_creates_request_and_assigns_approver(): void
    {
        $this->actingAs($this->requester);
        $pr = $this->makePR();

        $request = ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);

        $this->assertNotNull($request);
        $this->assertEquals('PENDING', $request->status);
        $this->assertEquals('SUBMITTED', $pr->fresh()->status);
        $action = $request->actions()->first();
        $this->assertNotNull($action);
        $this->assertEquals($this->approver->id, $action->approver_id);
        $this->assertEquals('PENDING', $action->action);
    }

    public function test_unauthorized_user_cannot_approve(): void
    {
        $this->actingAs($this->requester);
        $pr = $this->makePR();
        $request = ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);
        $action = $request->actions()->first();

        $this->assertFalse(ApprovalService::actOnActionId($action->id, $this->outsider, 'APPROVE'));
        $this->assertEquals('PENDING', $request->fresh()->status);
    }

    public function test_authorized_approve_completes_and_applies_status(): void
    {
        $this->actingAs($this->requester);
        $pr = $this->makePR();
        $request = ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);
        $action = $request->actions()->first();

        $this->assertTrue(ApprovalService::actOnActionId($action->id, $this->approver, 'APPROVE', 'Setuju'));

        $this->assertEquals('APPROVED', $request->fresh()->status);
        $this->assertEquals('APPROVED', $pr->fresh()->status);
        $this->assertEquals('APPROVE', $action->fresh()->action);
    }

    public function test_reject_returns_transaction_to_rejected(): void
    {
        $this->actingAs($this->requester);
        $pr = $this->makePR();
        $request = ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);
        $action = $request->actions()->first();

        $this->assertTrue(ApprovalService::actOnActionId($action->id, $this->approver, 'REJECT', 'Ditolak'));
        $this->assertEquals('REJECTED', $request->fresh()->status);
        $this->assertEquals('REJECTED', $pr->fresh()->status);
    }

    public function test_delegate_can_approve_on_behalf(): void
    {
        \App\Models\ApprovalDelegation::create([
            'user_id' => $this->approver->id,
            'delegate_id' => $this->outsider->id,
            'start_date' => today()->subDay(),
            'end_date' => today()->addDay(),
            'is_active' => true,
        ]);

        $this->actingAs($this->requester);
        $pr = $this->makePR();
        $request = ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);
        $action = $request->actions()->first();

        $this->assertTrue(ApprovalService::actOnActionId($action->id, $this->outsider, 'APPROVE'));
        $this->assertEquals('APPROVED', $request->fresh()->status);
    }
}
