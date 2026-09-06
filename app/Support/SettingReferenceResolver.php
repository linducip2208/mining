<?php

namespace App\Support;

use App\Models\Pit;
use App\Models\Site;
use App\Models\Warehouse;

/**
 * Resolves model_select settings from a fixed server-side allow-list.
 * No model class or column can be supplied by the browser.
 */
final class SettingReferenceResolver
{
    private const RESOLVERS = [
        'warehouse' => Warehouse::class,
        'site' => Site::class,
        'pit' => Pit::class,
    ];

    public static function options(array $meta): array
    {
        $model = $meta['model'] ?? null;
        $class = self::RESOLVERS[$model] ?? null;
        if (! $class) {
            return [];
        }

        $labelColumn = in_array($meta['label_column'] ?? 'name', ['name', 'code'], true)
            ? $meta['label_column']
            : 'name';

        return $class::query()
            ->orderBy($labelColumn)
            ->limit(500)
            ->get(['id', $labelColumn])
            ->mapWithKeys(fn ($row) => [(string) $row->id => $row->{$labelColumn}])
            ->all();
    }

    public static function valid(string $key, mixed $value): bool
    {
        $meta = SettingCatalog::get($key);
        if (($meta['type'] ?? null) !== 'model_select') {
            return false;
        }

        return array_key_exists((string) $value, self::options($meta));
    }
}
