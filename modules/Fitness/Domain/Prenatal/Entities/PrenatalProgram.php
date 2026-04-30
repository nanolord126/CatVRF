<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Prenatal\Entities;

use Carbon\CarbonImmutable;

final readonly class PrenatalProgram
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $description,
        public string $targetTrimester,
        public int $durationWeeks,
        public int $sessionsPerWeek,
        public int $sessionDurationMinutes,
        public float $price,
        public ?int $maxParticipants,
        public ?array exercises,
        public ?array safetyGuidelines,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $description,
        string $targetTrimester,
        int $durationWeeks,
        int $sessionsPerWeek,
        int $sessionDurationMinutes,
        float $price,
        ?int $maxParticipants = null,
        ?array $exercises = null,
        ?array $safetyGuidelines = null,
        bool $isActive = true,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            targetTrimester: $targetTrimester,
            durationWeeks: $durationWeeks,
            sessionsPerWeek: $sessionsPerWeek,
            sessionDurationMinutes: $sessionDurationMinutes,
            price: $price,
            maxParticipants: $maxParticipants,
            exercises: $exercises,
            safetyGuidelines: $safetyGuidelines,
            isActive: $isActive,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }
}
