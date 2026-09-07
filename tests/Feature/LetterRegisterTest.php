<?php

namespace Tests\Feature;

use App\Models\LetterType;

class LetterRegisterTest extends AdminFlowTestCase
{
    public function test_create_draft_and_search_filters(): void
    {
        $this->post('/letters', [
            'letter_type_id' => LetterType::where('code', 'SP')->first()->id,
            'letter_date' => today()->toDateString(), 'subject' => 'Penawaran excavator',
            'recipient_type' => 'CUSTOMER', 'recipient_name' => 'PT Maju',
            'company_id' => $this->co->id, 'site_id' => $this->site->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('letter_registers', ['subject' => 'Penawaran excavator', 'status' => 'DRAFT']);
        $this->get('/letters?q=excavator')->assertOk()->assertSee('Penawaran excavator');
        $this->get('/letters?status=DRAFT')->assertOk();
    }

    public function test_full_workflow_to_archived(): void
    {
        $letter = $this->makeLetter();

        $this->post("/letters/{$letter->id}/reserve")->assertRedirect();
        $this->assertNotNull($letter->fresh()->number);
        $this->assertEquals('NUMBER_RESERVED', $letter->fresh()->status);

        $this->post("/letters/{$letter->id}/review")->assertRedirect();
        $this->assertContains($letter->fresh()->status, ['REVIEW', 'APPROVED']);

        $letter->update(['status' => 'REVIEW']);
        $this->post("/letters/{$letter->id}/approve")->assertRedirect();
        $this->assertEquals('APPROVED', $letter->fresh()->status);

        $this->post("/letters/{$letter->id}/sign")->assertRedirect();
        $this->assertEquals('SIGNED', $letter->fresh()->status);

        $this->post("/letters/{$letter->id}/send")->assertRedirect();
        $this->assertEquals('SENT', $letter->fresh()->status);

        $this->post("/letters/{$letter->id}/archive")->assertRedirect();
        $this->assertEquals('ARCHIVED', $letter->fresh()->status);
    }

    public function test_simple_publish_workflow(): void
    {
        $letter = $this->makeLetter();

        $this->post("/letters/{$letter->id}/publish")->assertRedirect();
        $this->assertEquals('PUBLISHED', $letter->fresh()->status);
    }

    public function test_update_blocked_after_review(): void
    {
        $letter = $this->makeLetter(['status' => 'REVIEW']);

        $this->put("/letters/{$letter->id}", ['subject' => 'Diubah'])->assertRedirect();
        $this->assertNotEquals('Diubah', $letter->fresh()->subject);
    }
}
