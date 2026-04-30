<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Entities;

use Carbon\CarbonImmutable;

final readonly class KidsSessionLog
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $enrollmentId,
        public CarbonImmutable $sessionDate,
        public ?string $activityType,
        public ?int $durationMinutes,
        public ?string $mood,
        public ?string $notes,
        public ?array vitals,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $enrollmentId,
        CarbonImmutable $sessionDate,
        ?string $activityType = null,
        ?int $durationMinutes = null,
        ?string $mood = null,
        ?string $notes = null,
        ?array $vitals = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            enrollmentId: $enrollmentId,
            sessionDate: $sessionDate,
            activityType: $activityType,
            durationMinutes: $durationMinutes,
            mood: $mood,
            notes: $notes,
            vitals: $vitals,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }
}
