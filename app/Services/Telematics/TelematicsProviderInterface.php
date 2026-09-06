<?php

namespace App\Services\Telematics;

use App\Models\TelematicsEvent;

/**
 * Provider interface — never hardcode a vendor.
 * Each driver normalizes vendor payloads into TelematicsEvent rows.
 */
interface TelematicsProviderInterface
{
    public function code(): string;

    /** Pull latest data and store normalized events. Returns events created. */
    public function sync(?int $providerId = null): int;

    /** Normalize one raw payload into event attributes (without id/provider). */
    public function normalize(array $payload): array;
}
