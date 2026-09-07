<?php

namespace App\Services;

/**
 * Centralized Indonesian number-to-words (terbilang).
 * Single source for invoice, receipt, payment and finance documents.
 */
final class NumberToWordsService
{
    private const UNITS = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

    public static function rupiah(float|int $amount): string
    {
        $rounded = (int) round($amount);
        if ($rounded === 0) {
            return 'Nol Rupiah';
        }
        $prefix = $rounded < 0 ? 'Minus ' : '';

        return $prefix.trim(self::spell(abs($rounded))).' Rupiah';
    }

    public static function spell(int $number): string
    {
        if ($number < 12) {
            return self::UNITS[$number];
        }
        if ($number < 20) {
            return self::spell($number - 10).' Belas';
        }
        if ($number < 100) {
            return trim(self::spell(intdiv($number, 10)).' Puluh '.self::spell($number % 10));
        }
        if ($number < 200) {
            return trim('Seratus '.self::spell($number - 100));
        }
        if ($number < 1000) {
            return trim(self::spell(intdiv($number, 100)).' Ratus '.self::spell($number % 100));
        }
        if ($number < 2000) {
            return trim('Seribu '.self::spell($number - 1000));
        }
        if ($number < 1000000) {
            return trim(self::spell(intdiv($number, 1000)).' Ribu '.self::spell($number % 1000));
        }
        if ($number < 1000000000) {
            return trim(self::spell(intdiv($number, 1000000)).' Juta '.self::spell($number % 1000000));
        }
        if ($number < 1000000000000) {
            return trim(self::spell(intdiv($number, 1000000000)).' Miliar '.self::spell($number % 1000000000));
        }

        return trim(self::spell(intdiv($number, 1000000000000)).' Triliun '.self::spell($number % 1000000000000));
    }
}
