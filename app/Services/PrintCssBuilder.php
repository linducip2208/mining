<?php

namespace App\Services;

use App\Models\PaperProfile;

final class PrintCssBuilder
{
    public static function page(PaperProfile $paper, ?string $orientation = null): string
    {
        $orientation = strtoupper($orientation ?: $paper->orientation);
        $width = (float) $paper->width_mm;
        $height = $paper->height_mm === null ? null : (float) $paper->height_mm;

        if ($orientation === 'LANDSCAPE' && $height !== null) {
            [$width, $height] = [$height, $width];
        }

        $size = $height === null ? $width.'mm auto' : $width.'mm '.$height.'mm';

        return '@page { size: '.$size.'; margin: '.$paper->margin_top_mm.'mm '.$paper->margin_right_mm.'mm '.$paper->margin_bottom_mm.'mm '.$paper->margin_left_mm.'mm; }';
    }

    public static function dimensions(PaperProfile $paper, ?string $orientation = null): array
    {
        $width = (float) $paper->width_mm;
        $height = $paper->height_mm === null ? null : (float) $paper->height_mm;
        if (strtoupper($orientation ?: $paper->orientation) === 'LANDSCAPE' && $height !== null) {
            [$width, $height] = [$height, $width];
        }

        return ['width' => $width, 'height' => $height];
    }
}
