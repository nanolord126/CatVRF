<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * Cohort Data DTO
 *
 * Represents a single cohort with retention rates over time.
 */
final readonly class CohortDataDto
{
    /**
     * @param array<int, float> $retentionRates Indexed by period (0 = initial, 1 = period 1, etc.)
     */
    public function __construct(
        public readonly string $cohortName,
        public readonly CarbonImmutable $cohortDate,
        public readonly int $cohortSize,
        public readonly array $retentionRates,
    ) {}

    /**
     * @param array<int, float> $retentionRates
     */
    public static function create(
        string $cohortName,
        CarbonImmutable $cohortDate,
        int $cohortSize,
        array $retentionRates,
    ): self {
        return new self(
            $cohortName,
            $cohortDate,
            $cohortSize,
            $retentionRates,
        );
    }

    public function getRetentionRate(int $period): float|null
    {
        return $this->retentionRates[$period] ?? null;
    }

    public function toArray(): array
    {
        return [
            'cohort_name' => $this->cohortName,
            'cohort_date' => $this->cohortDate->toIso8601String(),
            'cohort_size' => $this->cohortSize,
            'retention_rates' => $this->retentionRates,
        ];
    }
}
