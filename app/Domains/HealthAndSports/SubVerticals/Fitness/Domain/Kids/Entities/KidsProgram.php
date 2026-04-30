<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Entities;

use Carbon\CarbonImmutable;

final readonly class KidsProgram
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $description,
        public string $targetAgeGroup,
        public int $durationWeeks,
        public int $sessionsPerWeek,
        public int $sessionDurationMinutes,
        public float $price,
        public ?int $maxParticipants,
        public ?array activities,
        public ?array safetyGuidelines,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $description,
        string $targetAgeGroup,
        int $durationWeeks,
        int $sessionsPerWeek,
        int $sessionDurationMinutes,
        float $price,
        ?int $maxParticipants = null,
        ?array $activities = null,
        ?array $safetyGuidelines = null,
        bool $isActive = true,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            targetAgeGroup: $targetAgeGroup,
            durationWeeks: $durationWeeks,
            sessionsPerWeek: $sessionsPerWeek,
            sessionDurationMinutes: $sessionDurationMinutes,
            price: $price,
            maxParticipants: $maxParticipants,
            activities: $activities,
            safetyGuidelines: $safetyGuidelines,
            isActive: $isActive,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }
}
