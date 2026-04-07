<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Data\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

/**
 * Class AnalyticsEventData
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Analytics\Data\DTOs
 */
final readonly class AnalyticsEventData extends Data
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
}
