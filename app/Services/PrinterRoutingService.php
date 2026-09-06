<?php

namespace App\Services;

use App\Models\PrinterDevice;
use App\Models\Setting;

final class PrinterRoutingService
{
    public function resolve(string $documentType, ?int $companyId = null, ?int $siteId = null, ?string $workstation = null): ?PrinterDevice
    {
        $documentType = strtoupper($documentType);
        $devices = PrinterDevice::query()->where('is_active', true)->get();

        $candidate = $devices
            ->filter(fn (PrinterDevice $device) => $device->supports($documentType))
            ->filter(fn (PrinterDevice $device) => $this->score($device, $companyId, $siteId, $workstation) > -1000)
            ->sortByDesc(fn (PrinterDevice $device) => $this->score($device, $companyId, $siteId, $workstation))
            ->first();

        if ($candidate) {
            return $candidate;
        }

        $settingKey = str_contains($documentType, 'WEIGHBRIDGE') || str_contains($documentType, 'FUEL')
            ? 'printer.default_weighbridge_printer_id'
            : (str_contains($documentType, 'REPORT') || in_array($documentType, ['INVOICE', 'PURCHASE_ORDER', 'PURCHASE_REQUEST', 'DELIVERY_ORDER', 'PAYSLIP'], true)
                ? 'printer.default_a4_printer_id'
                : 'printer.default_thermal_printer_id');
        $id = Setting::get($settingKey);

        return $id ? $devices->firstWhere('id', (int) $id) : null;
    }

    private function score(PrinterDevice $device, ?int $companyId, ?int $siteId, ?string $workstation): int
    {
        if ($companyId && $device->company_id && $device->company_id !== $companyId) {
            return -1000;
        }
        if ($siteId && $device->site_id && $device->site_id !== $siteId) {
            return -1000;
        }
        if ($workstation && $device->workstation && strcasecmp($device->workstation, $workstation) !== 0) {
            return -1000;
        }

        return ($device->is_default ? 10 : 0)
            + ($siteId && $device->site_id === $siteId ? 80 : 0)
            + ($companyId && $device->company_id === $companyId ? 40 : 0)
            + ($workstation && $device->workstation === $workstation ? 100 : 0);
    }
}
