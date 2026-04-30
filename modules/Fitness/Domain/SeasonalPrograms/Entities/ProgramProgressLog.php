<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Entities;

use Carbon\CarbonImmutable;

final readonly class ProgramProgressLog
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $enrollmentId,
        public CarbonImmutable $date,
        public ?array $metrics,
        public ?string $notes,
        public ?array $photos,
        public ?float $weight,
        public ?float $bodyFatPercentage,
        public ?array $measurements,
        public ?int $wellbeingScore,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $enrollmentId,
        CarbonImmutable $date,
        ?array $metrics = null,
        ?string $notes = null,
        ?array $photos = null,
        ?float $weight = null,
        ?float $bodyFatPercentage = null,
        ?array $measurements = null,
        ?int $wellbeingScore = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            enrollmentId: $enrollmentId,
            date: $date,
            metrics: $metrics,
            notes: $notes,
            photos: $photos,
            weight: $weight,
            bodyFatPercentage: $bodyFatPercentage,
            measurements: $measurements,
            wellbeingScore: $wellbeingScore,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function hasPhotos(): bool
    {
        return $this->photos !== null && count($this->photos) > 0;
    }

    public function getWeightChange(?float $previousWeight): ?float
    {
        if ($this->weight === null || $previousWeight === null) {
            return null;
        }

        return $this->weight - $previousWeight;
    }
}
