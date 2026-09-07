<?php

namespace Tests\Feature;

class LetterVoidTest extends AdminFlowTestCase
{
    public function test_void_releases_number_as_void_never_reused(): void
    {
        $letter = $this->makeLetter();
        $this->post("/letters/{$letter->id}/reserve")->assertRedirect();
        $number = $letter->fresh()->number;
        $this->assertNotNull($number);

        $this->post("/letters/{$letter->id}/void", ['reason' => 'salah ketik'])->assertRedirect();
        $this->assertEquals('VOID', $letter->fresh()->status);
        $this->assertEquals('VOID', $letter->fresh()->reservation->status);

        // next reservation must get a different number (void never reused)
        $letter2 = $this->makeLetter();
        $this->post("/letters/{$letter2->id}/reserve")->assertRedirect();
        $this->assertNotEquals($number, $letter2->fresh()->number);
    }

    public function test_cancel_before_approval(): void
    {
        $letter = $this->makeLetter();
        $this->post("/letters/{$letter->id}/cancel")->assertRedirect();
        $this->assertEquals('CANCELLED', $letter->fresh()->status);
    }
}
