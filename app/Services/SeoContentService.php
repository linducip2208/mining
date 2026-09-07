<?php

namespace App\Services;

use App\Models\SeoFeature;
use App\Models\SeoIndustry;
use App\Models\SeoLocation;
use App\Models\SeoUseCase;

/**
 * Deterministic content composer. Same entity combination always yields the
 * same blocks — no random text, no spun variations.
 */
final class SeoContentService
{
    public const PROBLEMS = [
        'Pencatatan masih terpisah di Excel sehingga data antar site berbeda.',
        'Approval masih manual lewat chat sehingga keputusan lambat dan tak terdokumentasi.',
        'Stok dan stockpile sulit ditelusuri dari produksi sampai penjualan.',
        'Data timbangan tidak terintegrasi dengan pengiriman dan faktur.',
        'Pemakaian BBM unit tidak terpantau sehingga rawan fuel leakage.',
        'Maintenance alat berat tidak terkontrol dan downtime tak tercatat.',
        'Invoice dan accounting terpisah sehingga laporan keuangan terlambat.',
    ];

    public const BENEFITS = [
        ['title' => 'Dapat dikembangkan', 'body' => 'Source code dapat dilanjutkan oleh tim internal mengikuti workflow perusahaan.'],
        ['title' => 'White-label', 'body' => 'Nama, logo, dan warna aplikasi dapat disesuaikan dengan identitas perusahaan.'],
        ['title' => 'Self-hosted', 'body' => 'Dijalankan di server sendiri sehingga data operasional tetap di bawah kendali perusahaan.'],
        ['title' => 'Dapat diintegrasikan', 'body' => 'Dapat dihubungkan dengan timbangan, fingerprint, maupun sistem yang sudah berjalan.'],
        ['title' => 'Tidak terkunci SaaS', 'body' => 'Tidak bergantung pada UI maupun paket berlangganan vendor tertentu.'],
    ];

    public const DEFAULT_WORKFLOW = ['Mining', 'Hauling', 'Weighbridge', 'Stockpile', 'Crusher', 'Inventory', 'Sales', 'Accounting'];

    /**
     * @return array{hero:array, problems:array, solution:string, workflow:array, features:array, benefits:array, price:array, faqs:array}
     */
    public static function compose(
        string $keyword,
        string $intent,
        ?SeoFeature $feature = null,
        ?SeoIndustry $industry = null,
        ?SeoLocation $location = null,
        ?SeoUseCase $useCase = null
    ): array {
        $subject = self::subject($keyword, $feature, $industry, $location, $useCase);
        $workflow = self::workflow($feature, $industry, $useCase);

        return [
            'hero' => [
                'eyebrow' => 'Source Code ERP Mining',
                'subheadline' => "Kelola {$subject} dalam satu sistem terintegrasi — dari operasional lapangan hingga laporan keuangan.",
                'price_line' => 'Source Code mulai '.WhatsappService::priceFormatted().' ('.WhatsappService::priceShort().')',
            ],
            'problems' => self::problems($feature, $industry),
            'solution' => self::solution($subject, $workflow),
            'workflow' => $workflow,
            'features' => self::features($feature, $useCase),
            'benefits' => self::BENEFITS,
            'price' => [
                'amount' => WhatsappService::PRICE_AMOUNT,
                'display' => WhatsappService::priceFormatted(),
                'short' => WhatsappService::priceShort(),
                'note' => 'Harga merupakan harga mulai. Biaya akhir mengikuti scope, modul, integrasi, deployment, dan customization.',
            ],
            'faqs' => self::faqs($keyword, $subject, $feature, $industry, $location),
        ];
    }

    public static function subject(
        string $keyword,
        ?SeoFeature $feature = null,
        ?SeoIndustry $industry = null,
        ?SeoLocation $location = null,
        ?SeoUseCase $useCase = null
    ): string {
        $parts = [ucwords($keyword)];
        if ($industry) {
            $parts[] = 'untuk '.$industry->name;
        }
        if ($location) {
            $parts[] = 'di '.$location->name;
        }

        return implode(' ', $parts);
    }

    /**
     * @return string[]
     */
    public static function workflow(
        ?SeoFeature $feature = null,
        ?SeoIndustry $industry = null,
        ?SeoUseCase $useCase = null
    ): array {
        if ($useCase && is_array($useCase->feature_slugs) && $useCase->feature_slugs !== []) {
            $names = SeoFeature::whereIn('slug', $useCase->feature_slugs)->orderBy('priority')->pluck('name')->all();

            return $names !== [] ? array_values($names) : self::DEFAULT_WORKFLOW;
        }
        if ($industry && is_array($industry->workflow) && $industry->workflow !== []) {
            return array_values($industry->workflow);
        }
        if ($feature && is_array($feature->workflow) && $feature->workflow !== []) {
            return array_values($feature->workflow);
        }

        return self::DEFAULT_WORKFLOW;
    }

    /**
     * @return string[]
     */
    protected static function problems(?SeoFeature $feature, ?SeoIndustry $industry): array
    {
        $problems = self::PROBLEMS;
        if ($feature) {
            array_unshift($problems, $feature->business_problem);
        }

        return array_values(array_slice(array_unique($problems), 0, 7));
    }

    protected static function solution(string $subject, array $workflow): string
    {
        return 'Mining ERP menghubungkan '.$subject.' dalam satu alur: '
            .implode(' → ', $workflow)
            .'. Setiap tahap tercatat, dapat ditelusuri maju-mundur, dan mengalir ke stok serta jurnal akuntansi tanpa input ulang.';
    }

    /**
     * @return array<int, array{name:string, slug:string, short_description:string}>
     */
    protected static function features(?SeoFeature $feature, ?SeoUseCase $useCase): array
    {
        $slugs = [];
        if ($feature) {
            $slugs[] = $feature->slug;
        }
        if ($useCase && is_array($useCase->feature_slugs)) {
            $slugs = array_values(array_unique(array_merge($slugs, $useCase->feature_slugs)));
        }
        // focus entity first, then fill with top marketable features so every
        // page carries a substantive feature set (quality gate: min 3)
        $fill = SeoFeature::marketable()->orderBy('priority')->pluck('slug')->all();
        $slugs = array_values(array_unique(array_merge($slugs, $fill)));

        $ordered = SeoFeature::marketable()->whereIn('slug', array_slice($slugs, 0, 8))->get()->keyBy('slug');
        $features = collect(array_slice($slugs, 0, 8))->map(fn ($s) => $ordered->get($s))->filter()->values();

        return $features->map(fn (SeoFeature $f) => [
            'name' => $f->name,
            'slug' => $f->slug,
            'short_description' => $f->short_description,
        ])->all();
    }

    /**
     * @return array<int, array{q:string, a:string}>
     */
    protected static function faqs(
        string $keyword,
        string $subject,
        ?SeoFeature $feature,
        ?SeoIndustry $industry,
        ?SeoLocation $location
    ): array {
        $kw = ucwords($keyword);
        $faqs = [
            [
                'q' => "Berapa harga {$kw}?",
                'a' => 'Source code mulai '.WhatsappService::priceFormatted().' ('.WhatsappService::priceShort().'). Harga merupakan harga mulai; biaya akhir mengikuti scope, modul, integrasi, deployment, dan customization.',
            ],
            [
                'q' => "Apakah {$kw} berupa source code?",
                'a' => 'Ya. Yang ditawarkan adalah source code aplikasi ERP pertambangan yang dapat dikembangkan, white-label, dan self-hosted. Detail lisensi source code mengikuti penawaran/perjanjian.',
            ],
            [
                'q' => "Alur apa saja yang didukung untuk {$subject}?",
                'a' => 'Alur yang didukung: '.implode(', ', self::workflow($feature, $industry)).'.',
            ],
            [
                'q' => 'Apakah data operasional tetap milik perusahaan?',
                'a' => 'Ya. Aplikasi dijalankan di server sendiri (self-hosted) sehingga data tetap di bawah kendali perusahaan.',
            ],
            [
                'q' => 'Bagaimana cara meminta demo atau penawaran?',
                'a' => 'Hubungi via WhatsApp 0812-9605-2010 atau tombol Minta Demo di halaman ini untuk diskusi scope dan kebutuhan implementasi.',
            ],
        ];
        if ($location) {
            $faqs[] = [
                'q' => "Apakah cocok untuk perusahaan di {$location->name}?",
                'a' => "Ya. Aplikasi dirancang untuk operasi multi-site dengan data scope per site, approval lintas lokasi, dan dashboard manajemen terpusat — cocok untuk perusahaan di {$location->name}.",
            ];
        }
        if ($feature) {
            array_splice($faqs, 2, 0, [[
                'q' => "Masalah apa yang diselesaikan fitur {$feature->name}?",
                'a' => $feature->business_problem.' '.$feature->solution,
            ]]);
        }

        return array_values(array_slice($faqs, 0, 8));
    }
}
