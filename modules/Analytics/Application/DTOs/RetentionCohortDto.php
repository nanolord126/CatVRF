<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * Retention Cohort DTO
 *
 * Represents cohort retention analysis data.
 */
final readonly class RetentionCohortDto
{
    /**
     * @param CohortDataDto[] $cohorts
     */
    public function __construct(
        public readonly string $cohortType, // user, seller
        public readonly CarbonImmutable $analysisDate,
        public readonly array $cohorts,
        public readonly ?int $tenantId = null,
    ) {
        if (empty($cohorts)) {
            throw new \InvalidArgumentException('Retention cohort must have at least one cohort');
        }
    }

    /**
     * @param CohortDataDto[] $cohorts
     */
    public static function create(
        string $cohortType,
        CarbonImmutable $analysisDate,
        array $cohorts,
        ?int $tenantId = null,
    ): self {
        return new self(
            $cohortType,
            $analysisDate,
            $cohorts,
            $tenantId,
        );
    }

    public function toArray(): array
    {
        return [
            'cohort_type' => $this->cohortType,
            'analysis_date' => $this->analysisDate->toIso8601String(),
            'cohorts' => array_map(fn ($cohort) => $cohort->toArray(), $this->cohorts),
            'tenant_id' => $this->tenantId,
        ];
    }
}

