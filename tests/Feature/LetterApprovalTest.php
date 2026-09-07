<?php

namespace Tests\Feature;

use App\Services\ApprovalService;

class LetterApprovalTest extends AdminFlowTestCase
{
    public function test_review_submits_to_approval_center_or_auto_approves(): void
    {
        $letter = $this->makeLetter(['status' => 'NUMBER_RESERVED', 'number' => '001/SP-BFJ/I/2026']);

        $this->post("/letters/{$letter->id}/review")->assertRedirect();
        $this->assertContains($letter->fresh()->status, ['REVIEW', 'APPROVED']);
    }

    public function test_center_approval_applies_approved_status(): void
    {
        $letter = $this->makeLetter(['status' => 'NUMBER_RESERVED', 'number' => '002/SP-BFJ/I/2026']);
        $request = ApprovalService::submit('ADMINISTRATION', 'LETTER', $letter);

        if ($request) {
            $action = $request->actions()->first();
            $this->assertTrue(ApprovalService::actOnActionId($action->id, $this->admin, 'APPROVE'));
            $this->assertEquals('APPROVED', $letter->fresh()->status);
        } else {
            $this->assertEquals('APPROVED', $letter->fresh()->status);
        }
    }
}
