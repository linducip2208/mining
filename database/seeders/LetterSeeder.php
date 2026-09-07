<?php

namespace Database\Seeders;

use App\Models\LetterType;
use Illuminate\Database\Seeder;

class LetterSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['SP', 'Surat Penawaran'], ['PKL', 'Surat Paklaring'], ['BLS', 'Surat Balasan'],
            ['BA', 'Berita Acara'], ['PMH', 'Surat Permohonan'], ['PGM', 'Pengumuman'],
            ['BAST', 'BAST'], ['DUK', 'Surat Dukungan'], ['PH', 'Surat Perubahan Harga'],
            ['PO', 'Purchase Order'], ['SK', 'Surat Keputusan'], ['INT', 'Surat Internal'],
            ['EXT', 'Surat Eksternal'], ['HRD', 'Surat HRD'],
        ];
        foreach ($types as [$code, $name]) {
            LetterType::updateOrCreate(['code' => $code], [
                'name' => $name,
                'numbering_format' => '{SEQ:3}/{TYPE}-{COMPANY}/{MONTH_ROMAN}/{YEAR}',
                'reset_period' => 'YEARLY',
                'is_active' => true,
            ]);
        }
    }
}
