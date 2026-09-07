<?php

namespace Tests\Feature;

use App\Models\LetterNumberReservation;
use App\Models\LetterType;
use App\Services\LetterService;

class LetterNumberConcurrencyTest extends AdminFlowTestCase
{
    public function test_parallel_reserves_yield_unique_numbers(): void
    {
        $type = LetterType::where('code', 'BA')->first();
        $numbers = [];
        for ($i = 0; $i < 5; $i++) {
            $numbers[] = LetterService::reserveNumber($type, ['TYPE' => 'BA', 'COMPANY' => 'BFJ'])->number;
        }

        $this->assertEquals(5, count(array_unique($numbers)));
        $this->assertEquals(5, LetterNumberReservation::where('status', 'RESERVED')->count());
    }

    public function test_commit_marks_used_and_links_letter(): void
    {
        $type = LetterType::where('code', 'SK')->first();
        $letter = $this->makeLetter();
        $res = LetterService::reserveNumber($type, ['TYPE' => 'SK', 'COMPANY' => 'BFJ']);
        LetterService::commitReservation($res, $letter);

        $this->assertEquals('USED', $res->fresh()->status);
        $this->assertEquals($res->number, $letter->fresh()->number);
    }
}
