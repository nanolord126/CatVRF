<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Domain\Entities;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Class AnalyticsEvent
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Analytics\Domain\Entities
 */
final class AnalyticsEvent
{
    public function __construct(
        private readonly string $uuid,
        private readonly int $tenant_id,
        private readonly ?int $user_id,
        private readonly string $event_type,
        private readonly array $payload,
        private readonly string $vertical,
        private readonly ?string $ip_address,
        private readonly ?string $device_fingerprint,
        private readonly Carbon $created_at,
        private readonly string $correlation_id
    ) {
}

    public static function create(
        int $tenant_id,
        ?int $user_id,
        string $event_type,
        array $payload,
        string $vertical,
        ?string $ip_address,
        ?string $device_fingerprint,
        string $correlation_id
    ): self {
        return new self(
            uuid: Str::uuid()->toString(),
            tenant_id: $tenant_id,
            user_id: $user_id,
            event_type: $event_type,
            payload: $payload,
            vertical: $vertical,
            ip_address: $ip_address,
            device_fingerprint: $device_fingerprint,
            created_at: Carbon::now(),
            correlation_id: $correlation_id
        );
    }
}
