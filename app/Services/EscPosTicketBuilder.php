<?php

namespace App\Services;

final class EscPosTicketBuilder
{
    public static function weighbridge($ticket): string
    {
        $lines = [
            self::center(self::bold('MINING ERP')),
            self::center(BrandingService::companyName()),
            self::rule(),
            self::center(self::bold('TIKET TIMBANG')),
            self::rule(),
            self::row('Nomor', $ticket->ticket_no),
            self::row('Tanggal', $ticket->second_weigh_at?->format('d/m/Y H:i') ?? $ticket->first_weigh_at?->format('d/m/Y H:i')),
            self::row('Kendaraan', $ticket->vehicle_plate ?: '—'),
            self::row('Driver', $ticket->driver_name ?: '—'),
            self::row('Produk', $ticket->item?->name ?: '—'),
            self::row('Site', $ticket->site?->name ?: '—'),
            self::rule(),
            self::row('GROSS', number_format((float) $ticket->gross, 0, ',', '.').' kg'),
            self::row('TARE', number_format((float) $ticket->tare, 0, ',', '.').' kg'),
            self::row('NET', number_format((float) $ticket->net, 0, ',', '.').' kg'),
            self::rule(),
            self::row('Operator', $ticket->operator?->name ?: '—'),
            '',
            self::center($ticket->ticket_no),
            '',
        ];

        return "\x1b@".implode("\n", $lines)."\n";
    }

    private static function row(string $label, string $value): string
    {
        $value = mb_strimwidth($value, 0, 22, '');

        return str_pad($label, 12).$value;
    }

    private static function center(string $value): string
    {
        return "\x1ba\x01".$value."\x1ba\x00";
    }

    private static function bold(string $value): string
    {
        return "\x1bE\x01".$value."\x1bE\x00";
    }

    private static function rule(): string
    {
        return str_repeat('-', 32);
    }
}
