<?php

namespace App\Services\Bfj;

use App\Models\BfjMasterAlias;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\LegacyImportMatch;
use App\Models\Vehicle;

/**
 * Master matching with explicit review states (§5-§6, §74-§75).
 * Auto-link ONLY on exact normalized match or explicit alias.
 * Fuzzy suggestions never auto-link financial/stock masters.
 */
final class BfjMasterMatcher
{
    /** @return array{target_type:string|null,target_id:int|null,status:string,confidence:int} */
    public static function match(string $entity, string $legacyValue): array
    {
        $normalized = match ($entity) {
            'CUSTOMER' => BfjNormalizer::normalizeCustomer($legacyValue),
            'VEHICLE' => BfjNormalizer::normalizePlate($legacyValue),
            'MATERIAL', 'SPAREPART' => BfjNormalizer::normalizeMaterial($legacyValue),
            'COMPANY' => BfjNormalizer::normalizeCompany($legacyValue),
            default => BfjNormalizer::upper($legacyValue),
        };

        $alias = BfjMasterAlias::where('entity_type', $entity)
            ->where('normalized_value', $normalized)
            ->whereNotNull('target_id')
            ->first();
        if ($alias) {
            return ['target_type' => $alias->target_type, 'target_id' => $alias->target_id, 'status' => 'ALIAS_MATCH', 'confidence' => 95];
        }

        $found = self::findExisting($entity, $normalized);
        if ($found) {
            return ['target_type' => $found[0], 'target_id' => $found[1], 'status' => 'EXACT_MATCH', 'confidence' => 100];
        }

        $suggestion = self::suggest($entity, $normalized);
        if ($suggestion) {
            return ['target_type' => $suggestion[0], 'target_id' => null, 'status' => 'POSSIBLE_MATCH', 'confidence' => $suggestion[1]];
        }

        return ['target_type' => null, 'target_id' => null, 'status' => 'NEW_MASTER_REQUIRED', 'confidence' => 0];
    }

    /** @return array{0:string,1:int}|null */
    private static function findExisting(string $entity, string $normalized): ?array
    {
        try {
            switch ($entity) {
                case 'CUSTOMER':
                    $c = Customer::query()->get()->first(fn ($r) => BfjNormalizer::normalizeCustomer($r->name ?? '') === $normalized);
                    if ($c) {
                        return [Customer::class, $c->id];
                    }
                    break;
                case 'VEHICLE':
                    foreach ([Vehicle::class, Equipment::class] as $cls) {
                        if (! class_exists($cls)) {
                            continue;
                        }
                        try {
                            $r = $cls::query()->get()->first(function ($row) use ($normalized) {
                                $plate = $row->plate_number ?? $row->code ?? $row->name ?? '';
                                if (! $plate) {
                                    return false;
                                }

                                return BfjNormalizer::normalizePlate((string) $plate) === $normalized;
                            });
                            if ($r) {
                                return [$cls, $r->id];
                            }
                        } catch (\Throwable) {
                        }
                    }
                    break;
                case 'MATERIAL':
                case 'SPAREPART':
                    $i = Item::whereRaw('UPPER(code) = ?', [$normalized])->first()
                        ?? Item::query()->get()->first(fn ($r) => BfjNormalizer::upper($r->name ?? '') === $normalized);
                    if ($i) {
                        return [Item::class, $i->id];
                    }
                    break;
                case 'EMPLOYEE':
                    $e = Employee::query()->get()->first(fn ($r) => BfjNormalizer::upper($r->name ?? '') === $normalized);
                    if ($e) {
                        return [Employee::class, $e->id];
                    }
                    break;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /** @return array{0:string,1:int}|null fuzzy suggestion (never auto-linked) */
    private static function suggest(string $entity, string $normalized): ?array
    {
        try {
            $candidates = match ($entity) {
                'CUSTOMER' => Customer::limit(200)->pluck('name')->all(),
                'EMPLOYEE' => Employee::limit(200)->pluck('name')->all(),
                'MATERIAL', 'SPAREPART' => Item::limit(200)->pluck('name')->all(),
                default => [],
            };
            $best = null;
            $bestScore = 0;
            foreach ($candidates as $c) {
                similar_text($normalized, BfjNormalizer::upper((string) $c), $pct);
                if ($pct > $bestScore) {
                    $bestScore = $pct;
                    $best = (string) $c;
                }
            }
            if ($bestScore >= 70) {
                return [$best, (int) $bestScore];
            }
        } catch (\Throwable) {
        }

        return null;
    }

    public static function record(int $batchId, string $entity, string $legacyValue, array $match): LegacyImportMatch
    {
        return LegacyImportMatch::create([
            'batch_id' => $batchId,
            'entity_type' => $entity,
            'legacy_value' => mb_substr($legacyValue, 0, 160),
            'normalized_value' => match ($entity) {
                'CUSTOMER' => BfjNormalizer::normalizeCustomer($legacyValue),
                'VEHICLE' => BfjNormalizer::normalizePlate($legacyValue),
                default => BfjNormalizer::upper($legacyValue),
            },
            'target_type' => $match['target_id'] ? $match['target_type'] : null,
            'target_id' => $match['target_id'],
            'confidence' => $match['confidence'],
            'status' => $match['status'],
        ]);
    }
}
