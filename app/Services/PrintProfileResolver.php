<?php

namespace App\Services;

use App\Models\DocumentPrintProfile;
use App\Models\PaperProfile;
use App\Models\PrintPreference;
use App\Models\PrinterDevice;

final class PrintProfileResolver
{
    private const DEFAULTS = [
        'WEIGHBRIDGE_TICKET' => 'THERMAL_80',
        'FUEL_RECEIPT' => 'THERMAL_80',
        'WIDE_REPORT' => 'A4_LANDSCAPE',
    ];

    public function resolve(
        string $documentType,
        ?int $companyId = null,
        ?int $siteId = null,
        ?string $workstationKey = null,
        ?int $printerId = null,
        ?int $userId = null,
        ?int $paperProfileId = null,
    ): array {
        $documentType = strtoupper($documentType);
        $printer = $printerId ? PrinterDevice::query()->where('is_active', true)->find($printerId) : null;
        $source = 'global';

        if ($userId && $workstationKey) {
            $preference = PrintPreference::query()->where('user_id', $userId)->where('workstation_key', $workstationKey)->first();
            if ($preference) {
                $printer ??= $preference->printer;
                $paper = $paperProfileId ? PaperProfile::query()->where('is_active', true)->find($paperProfileId) : $preference->paperProfile;
                if ($paper && $this->isCompatible($printer, $paper)) {
                    return $this->result($documentType, $paper, $printer, 'user_preference');
                }
            }
        }

        $scoped = DocumentPrintProfile::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->get()
            ->sortByDesc(fn (DocumentPrintProfile $profile): int => $this->scopeScore($profile, $companyId, $siteId, $workstationKey))
            ->first(fn (DocumentPrintProfile $profile): bool => $this->scopeMatches($profile, $companyId, $siteId, $workstationKey));

        if ($scoped) {
            $printer ??= $scoped->printer;
            $paper = $paperProfileId ? PaperProfile::query()->where('is_active', true)->find($paperProfileId) : $scoped->paperProfile;
            if ($paper && $this->isCompatible($printer, $paper)) {
                $source = $this->scopeScore($scoped, $companyId, $siteId, $workstationKey) >= 4 ? 'workstation' : ($scoped->site_id ? 'site' : ($scoped->company_id ? 'company' : 'document_default'));
                return $this->result($documentType, $paper, $printer, $source, $scoped);
            }
        }

        $paperCode = self::DEFAULTS[$documentType] ?? 'A4_PORTRAIT';
        $paper = PaperProfile::query()->where('code', $paperCode)->where('is_active', true)->first()
            ?? PaperProfile::query()->where('is_default', true)->where('is_active', true)->first();

        if ($paperProfileId) {
            $override = PaperProfile::query()->where('id', $paperProfileId)->where('is_active', true)->first();
            if ($override && $this->isCompatible($printer, $override)) {
                $paper = $override;
                $source = 'explicit_override';
            }
        }

        if ($printer && ! $this->isCompatible($printer, $paper)) {
            $paper = $this->compatibleDefault($printer) ?? $paper;
            $source = 'printer_default';
        }

        return $this->result($documentType, $paper, $printer, $source);
    }

    public function remember(int $userId, ?string $workstationKey, ?int $printerId, ?int $paperProfileId): void
    {
        PrintPreference::query()->updateOrCreate(
            ['user_id' => $userId, 'workstation_key' => $workstationKey],
            ['last_printer_id' => $printerId, 'last_paper_profile_id' => $paperProfileId]
        );
    }

    public function availablePapers(?PrinterDevice $printer = null)
    {
        return PaperProfile::query()->where('is_active', true)->get()->filter(fn (PaperProfile $paper): bool => $this->isCompatible($printer, $paper))->values();
    }

    public function isCompatible(?PrinterDevice $printer, ?PaperProfile $paper): bool
    {
        return ! $printer || ! $paper || $printer->supportsPaper($paper);
    }

    private function compatibleDefault(PrinterDevice $printer): ?PaperProfile
    {
        return $this->availablePapers($printer)->first(fn (PaperProfile $paper): bool => $paper->is_default)
            ?? $this->availablePapers($printer)->first();
    }

    private function scopeMatches(DocumentPrintProfile $profile, ?int $companyId, ?int $siteId, ?string $workstationKey): bool
    {
        return ($profile->company_id === null || $profile->company_id === $companyId)
            && ($profile->site_id === null || $profile->site_id === $siteId)
            && ($profile->workstation_key === null || $profile->workstation_key === $workstationKey);
    }

    private function scopeScore(DocumentPrintProfile $profile, ?int $companyId, ?int $siteId, ?string $workstationKey): int
    {
        if (! $this->scopeMatches($profile, $companyId, $siteId, $workstationKey)) {
            return -1;
        }

        return ($profile->workstation_key !== null ? 4 : 0) + ($profile->site_id !== null ? 2 : 0) + ($profile->company_id !== null ? 1 : 0);
    }

    private function result(string $documentType, ?PaperProfile $paper, ?PrinterDevice $printer, string $source, ?DocumentPrintProfile $profile = null): array
    {
        return [
            'document_type' => $documentType,
            'paper' => $paper,
            'printer' => $printer,
            'profile' => $profile,
            'source' => $source,
            'orientation' => $profile?->orientation ?: $paper?->orientation ?: 'PORTRAIT',
            'copies' => max(1, min(99, (int) ($profile?->copies ?: $printer?->copies ?: 1))),
            'auto_print' => (bool) ($profile?->auto_print ?? $printer?->auto_print ?? false),
            'auto_cut' => (bool) ($profile?->auto_cut && $printer?->supports_auto_cut),
            'print_logo' => (bool) ($profile?->print_logo ?? true),
            'print_qr' => (bool) ($profile?->print_qr ?? false),
            'print_signature' => (bool) ($profile?->print_signature ?? true),
            'print_watermark' => (bool) ($profile?->print_watermark ?? false),
        ];
    }
}
