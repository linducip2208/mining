<?php

namespace App\Services;

use App\Models\DocumentNumbering;
use App\Models\LetterNumberReservation;
use App\Models\LetterRegister;
use App\Models\LetterType;
use Illuminate\Support\Facades\DB;

/**
 * Letter numbering (per-type sequences via NumberingService) + number
 * reservation lifecycle. Reserved/used numbers are never reissued.
 */
final class LetterService
{
    public static function docTypeFor(LetterType $type): string
    {
        return 'LETTER_'.strtoupper($type->code);
    }

    /**
     * Ensure a numbering counter exists carrying the type's format/reset.
     */
    public static function ensureNumbering(LetterType $type): DocumentNumbering
    {
        $row = DocumentNumbering::firstOrNew(
            ['doc_type' => self::docTypeFor($type), 'company_id' => null, 'site_id' => null]
        );
        $row->format = $type->numbering_format ?: '{SEQ:3}/{TYPE}-{COMPANY}/{MONTH_ROMAN}/{YEAR}';
        $row->reset_period = $type->reset_period ?: 'YEARLY';
        if (! $row->exists) {
            $row->current_seq = 0;
            $row->padding = 3;
        }
        $row->save();

        return $row;
    }

    /**
     * @param  array<string,string>  $context  TYPE/DEPARTMENT/COMPANY/SITE tokens
     */
    public static function reserveNumber(LetterType $type, array $context = [], ?string $notes = null): LetterNumberReservation
    {
        return DB::transaction(function () use ($type, $context, $notes) {
            self::ensureNumbering($type);
            $number = NumberingService::generate(self::docTypeFor($type), null, null, $context);

            return LetterNumberReservation::create([
                'doc' => 'LETTER',
                'number' => $number,
                'status' => 'RESERVED',
                'reserved_by' => auth()->id(),
                'notes' => $notes,
            ]);
        });
    }

    public static function commitReservation(LetterNumberReservation $reservation, LetterRegister $letter): void
    {
        if ($reservation->status !== 'RESERVED') {
            throw new \DomainException('Nomor tidak dalam status RESERVED.');
        }
        DB::transaction(function () use ($reservation, $letter) {
            $reservation->update(['status' => 'USED', 'letter_register_id' => $letter->id, 'used_at' => now()]);
            $letter->update(['number' => $reservation->number]);
            AuditService::log('UPDATE', 'LETTER', $letter->id, LetterRegister::class, null, ['number' => $reservation->number, 'event' => 'number_used']);
        });
    }

    public static function releaseReservation(LetterNumberReservation $reservation, string $status, ?string $notes = null): void
    {
        if (! in_array($status, ['CANCELLED', 'VOID'], true)) {
            throw new \InvalidArgumentException('Status pelepasan tidak valid.');
        }
        $reservation->update(['status' => $status, 'notes' => $notes ?? $reservation->notes]);
        AuditService::log('UPDATE', 'LETTER', $reservation->letter_register_id, LetterRegister::class, null, ['number' => $reservation->number, 'event' => strtolower($status)]);
    }
}
