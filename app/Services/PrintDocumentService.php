<?php

namespace App\Services;

use App\Models\PaperProfile;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PrintDocumentService
{
    public static function context(array $extra = [], bool $pdf = false): array
    {
        $company = BrandingService::companyProfile();
        $path = BrandingService::get('document.logo') ?: BrandingService::get('branding.logo_main');
        $logo = self::asset($path, $pdf);
        $paper = $extra['paperProfile'] ?? null;
        $orientation = $extra['orientation'] ?? ($paper?->orientation ?: Setting::get('document.orientation', 'portrait'));
        $margins = $paper ? [
            'top' => $paper->margin_top_mm,
            'right' => $paper->margin_right_mm,
            'bottom' => $paper->margin_bottom_mm,
            'left' => $paper->margin_left_mm,
        ] : [
            'top' => Setting::get('document.margin_top', 12),
            'right' => Setting::get('document.margin_right', 12),
            'bottom' => Setting::get('document.margin_bottom', 14),
            'left' => Setting::get('document.margin_left', 12),
        ];

        return array_merge([
            'company' => $company,
            'appName' => BrandingService::appName(),
            'logo' => $logo,
            'printedAt' => now(),
            'printedBy' => auth()->user()?->name ?? 'Sistem',
            'paperProfile' => $paper,
            'paperSize' => $paper?->name ?: Setting::get('document.paper_size', 'A4'),
            'paperCss' => $paper ? PrintCssBuilder::page($paper, $orientation) : null,
            'orientation' => strtolower((string) $orientation),
            'margins' => $margins,
            'showNpwp' => (bool) Setting::get('document.show_npwp', true),
            'showAddress' => (bool) Setting::get('document.show_address', true),
            'showPhone' => (bool) Setting::get('document.show_phone', true),
            'showEmail' => (bool) Setting::get('document.show_email', true),
            'showQr' => (bool) Setting::get('document.show_qr', false),
            'showPageNumber' => (bool) Setting::get('document.show_page_number', true),
            'showSignature' => (bool) Setting::get('document.show_signature', true),
            'signatureName' => Setting::get('document.signature_name', ''),
            'signatureTitle' => Setting::get('document.signature_title', ''),
            'watermarkEnabled' => (bool) Setting::get('document.watermark_enabled', true),
            'watermarkOpacity' => (float) Setting::get('document.watermark_opacity', 0.12),
            'signatureMode' => Setting::get('document.signature_mode', 'manual'),
            'template' => Setting::get('document.template', 'modern'),
            'headerText' => Setting::get('document.header_text', ''),
            'footerText' => Setting::get('document.footer_text', ''),
            'watermark' => null,
        ], $extra);
    }

    public static function render(string $view, array $data = [], bool $pdf = false): string
    {
        return view($view, self::context($data, $pdf))->render();
    }

    public static function pdf(string $view, array $data, string $filename)
    {
        $html = self::render($view, $data, true);
        $profile = $data['paperProfile'] ?? null;
        $pdf = Pdf::loadHTML($html)->setPaper(self::paper($profile), self::orientation($profile));
        $pdf->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        return $pdf->download(Str::finish(Str::slug($filename), '.pdf'));
    }

    public static function paper(?PaperProfile $profile = null): string|array
    {
        if ($profile) {
            $dimensions = PrintCssBuilder::dimensions($profile);
            if ($dimensions['height'] !== null) {
                return [0, 0, $dimensions['width'] * 2.83465, $dimensions['height'] * 2.83465];
            }

            // Dompdf has no reliable auto-length page. Use a long fixed roll and keep browser print exact.
            return [0, 0, $dimensions['width'] * 2.83465, 1700];
        }

        return match (Setting::get('document.paper_size', 'A4')) {
            'A5' => 'A5', 'Letter' => 'letter', 'F4' => [0, 0, 612, 936], 'Continuous' => [0, 0, 612, 936], default => 'A4',
        };
    }

    public static function orientation(?PaperProfile $profile = null): string
    {
        $orientation = strtolower((string) ($profile?->orientation ?: Setting::get('document.orientation', 'portrait')));

        return $orientation === 'landscape' ? 'landscape' : 'portrait';
    }

    private static function asset(?string $path, bool $pdf): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        if (! $pdf) {
            return Storage::disk('public')->url($path);
        }
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }
}