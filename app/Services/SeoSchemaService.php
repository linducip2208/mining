<?php

namespace App\Services;

use App\Models\SeoPage;

/**
 * Structured data that strictly matches visible content.
 * No reviews, ratings, or fabricated customers — ever.
 */
final class SeoSchemaService
{
    public static function forPage(SeoPage $page, array $content, array $breadcrumbs): array
    {
        $url = $page->url();
        $graphs = [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => 'Mining ERP',
                'url' => url('/'),
            ],
            [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $page->title,
                'description' => $page->description,
                'isPartOf' => ['@id' => url('/').'#website'],
                'breadcrumb' => ['@id' => $url.'#breadcrumb'],
            ],
            [
                '@type' => 'SoftwareApplication',
                'name' => 'Mining ERP — Source Code ERP Pertambangan',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'url' => $url,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => (string) WhatsappService::PRICE_AMOUNT,
                    'priceCurrency' => 'IDR',
                    'description' => 'Harga mulai (starting price). Biaya akhir mengikuti scope, modul, integrasi, deployment, dan customization.',
                    'availability' => 'https://schema.org/InStock',
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumb',
                'itemListElement' => collect($breadcrumbs)->values()->map(fn ($crumb, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ])->all(),
            ],
        ];

        if (($content['faqs'] ?? []) !== []) {
            $graphs[] = [
                '@type' => 'FAQPage',
                'mainEntity' => collect($content['faqs'])->map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
                ])->all(),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graphs];
    }
}
