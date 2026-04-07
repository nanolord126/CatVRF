<?php

declare(strict_types=1);

/**
 * Class ForecastResult
 *
 * Part of the DemandForecast vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\DemandForecast\DTOs
 */
final readonly class ForecastResult
{
    public function __construct(
        public int $predicted_demand,
        public int $confidence_interval_lower,
        public int $confidence_interval_upper,
        public float $confidence_score,
        public array $features_json,
        public string $correlation_id) {}
}
