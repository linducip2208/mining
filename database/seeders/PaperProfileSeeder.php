<?php

namespace Database\Seeders;

use App\Models\DocumentPrintProfile;
use App\Models\PaperProfile;
use Illuminate\Database\Seeder;

class PaperProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            ['code' => 'A4_PORTRAIT', 'name' => 'A4 Portrait', 'paper_type' => 'STANDARD', 'width_mm' => 210, 'height_mm' => 297, 'orientation' => 'PORTRAIT', 'is_default' => true],
            ['code' => 'A4_LANDSCAPE', 'name' => 'A4 Landscape', 'paper_type' => 'STANDARD', 'width_mm' => 297, 'height_mm' => 210, 'orientation' => 'LANDSCAPE'],
            ['code' => 'A5_PORTRAIT', 'name' => 'A5 Portrait', 'paper_type' => 'STANDARD', 'width_mm' => 148, 'height_mm' => 210, 'orientation' => 'PORTRAIT'],
            ['code' => 'F4_PORTRAIT', 'name' => 'F4 / Folio', 'paper_type' => 'STANDARD', 'width_mm' => 210, 'height_mm' => 330, 'orientation' => 'PORTRAIT'],
            ['code' => 'LETTER_PORTRAIT', 'name' => 'Letter', 'paper_type' => 'STANDARD', 'width_mm' => 215.9, 'height_mm' => 279.4, 'orientation' => 'PORTRAIT'],
            ['code' => 'THERMAL_58', 'name' => 'Thermal 58mm', 'paper_type' => 'THERMAL', 'width_mm' => 58, 'height_mm' => null, 'orientation' => 'AUTO', 'margin_top_mm' => 2, 'margin_right_mm' => 2, 'margin_bottom_mm' => 2, 'margin_left_mm' => 2, 'is_continuous' => true],
            ['code' => 'THERMAL_80', 'name' => 'Thermal 80mm', 'paper_type' => 'THERMAL', 'width_mm' => 80, 'height_mm' => null, 'orientation' => 'AUTO', 'margin_top_mm' => 3, 'margin_right_mm' => 3, 'margin_bottom_mm' => 3, 'margin_left_mm' => 3, 'is_continuous' => true],
            ['code' => 'CONTINUOUS', 'name' => 'Continuous', 'paper_type' => 'CONTINUOUS', 'width_mm' => 241.3, 'height_mm' => null, 'orientation' => 'AUTO', 'margin_top_mm' => 5, 'margin_right_mm' => 5, 'margin_bottom_mm' => 5, 'margin_left_mm' => 5, 'is_continuous' => true],
            ['code' => 'CUSTOM', 'name' => 'Custom', 'paper_type' => 'CUSTOM', 'width_mm' => 210, 'height_mm' => 297, 'orientation' => 'PORTRAIT'],
        ];

        foreach ($profiles as $profile) {
            PaperProfile::updateOrCreate(['code' => $profile['code']], array_merge([
                'margin_top_mm' => 10, 'margin_right_mm' => 10, 'margin_bottom_mm' => 10, 'margin_left_mm' => 10,
                'is_continuous' => false, 'is_system' => true, 'is_default' => false, 'is_active' => true,
            ], $profile));
        }

        $defaults = [
            'REPORT' => 'A4_PORTRAIT', 'WIDE_REPORT' => 'A4_LANDSCAPE', 'INVOICE' => 'A4_PORTRAIT',
            'PURCHASE_ORDER' => 'A4_PORTRAIT', 'PURCHASE_REQUEST' => 'A4_PORTRAIT', 'DELIVERY_ORDER' => 'A4_PORTRAIT',
            'GOODS_RECEIPT' => 'A4_PORTRAIT', 'PAYSLIP' => 'A4_PORTRAIT', 'JOURNAL_VOUCHER' => 'A4_PORTRAIT',
            'WORK_ORDER' => 'A4_PORTRAIT', 'WEIGHBRIDGE_TICKET' => 'THERMAL_80', 'FUEL_RECEIPT' => 'THERMAL_80',
        ];

        foreach ($defaults as $documentType => $code) {
            DocumentPrintProfile::updateOrCreate(
                ['document_type' => $documentType, 'company_id' => null, 'site_id' => null, 'workstation_key' => null],
                ['paper_profile_id' => PaperProfile::where('code', $code)->value('id'), 'orientation' => null, 'copies' => 1, 'auto_print' => false, 'auto_cut' => false, 'print_logo' => true, 'print_qr' => false, 'print_signature' => true, 'print_watermark' => false, 'is_active' => true]
            );
        }
    }
}
