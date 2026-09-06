<?php

namespace App\Services;

use App\Models\PaperProfile;
use App\Models\PrinterDevice;

final class EscPosTicketBuilder
{
    public static function weighbridge($ticket, ?PaperProfile $paper = null, ?PrinterDevice $printer = null): string
    {
        $width = self::width($paper, $printer);
        $lines = [
            self::center(self::bold(BrandingService::appName()), $width),
            self::center(BrandingService::companyName(), $width),
            self::rule($width),
            self::center(self::bold('TIKET TIMBANG'), $width),
            self::rule($width),
            self::row('Nomor', $ticket->ticket_no, $width),
            self::row('Tanggal', $ticket->second_weigh_at?->format('d/m/Y H:i') ?? $ticket->first_weigh_at?->format('d/m/Y H:i'), $width),
            self::row('Kendaraan', $ticket->vehicle_plate ?: '—', $width),
            self::row('Driver', $ticket->driver_name ?: '—', $width),
            self::row('Produk', $ticket->item?->name ?: '—', $width),
            self::row('Site', $ticket->site?->name ?: '—', $width),
            self::rule($width),
            self::row('GROSS', number_format((float) $ticket->gross, 0, ',', '.').' kg', $width),
            self::row('TARE', number_format((float) $ticket->tare, 0, ',', '.').' kg', $width),
            self::row('NET', number_format((float) $ticket->net, 0, ',', '.').' kg', $width),
            self::rule($width),
            self::row('Operator', $ticket->operator?->name ?: '—', $width),
            '',
            self::center($ticket->ticket_no, $width),
            '',
        ];

        return "\x1b@".implode("\n", $lines)."\n";
    }

    public static function width(?PaperProfile $paper = null, ?PrinterDevice $printer = null): int
    {
        if ($printer) {
            return $printer->characterWidth($paper);
        }

        return ((float) ($paper?->width_mm ?? 80)) <= 58 ? 32 : 42;
    }

    private static function row(string $label, string $value, int $width): string
    {
        $labelWidth = min(12, max(8, (int) floor($width * .3)));
        $valueWidth = max(8, $width - $labelWidth);
        $wrapped = wordwrap($value, $valueWidth, "\n", true);
        $rows = explode("\n", $wrapped);
        $output = [];

        foreach ($rows as $index => $line) {
            $output[] = ($index === 0 ? str_pad($label, $labelWidth) : str_repeat(' ', $labelWidth)).$line;
        }

        return implode("\n", $output);
    }

    private static function center(string $value, int $width): string
    {
        $value = implode(' ', preg_split('/\s+/', trim($value)) ?: []);
        $value = mb_strimwidth($value, 0, $width, '');

        return "\x1ba\x01".str_pad($value, $width, ' ', STR_PAD_BOTH)."\x1ba\x00";
    }

    private static function bold(string $value): string
    {
        return "\x1bE\x01".$value."\x1bE\x00";
    }

    private static function rule(int $width): string
    {
        return str_repeat('-', $width);
    }
}