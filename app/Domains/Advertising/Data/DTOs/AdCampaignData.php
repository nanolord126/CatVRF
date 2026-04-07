<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Data\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

/**
 * Class AdCampaignData
 *
 * Part of the Advertising vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Advertising\Data\DTOs
 */
final class AdCampaignData extends Data
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private readonly int $tenant_id,
        private readonly string $name,
        private readonly string $status,
        private readonly Carbon $start_at,
        private readonly Carbon $end_at,
        private readonly int $budget,
        private readonly int $spent,
        private readonly string $pricing_model,
        private readonly array $targeting_criteria,
        private readonly ?string $correlation_id
    ) {}
}
