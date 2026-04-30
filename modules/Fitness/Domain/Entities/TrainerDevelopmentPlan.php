<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainerDevelopmentPlan
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public string $uuid,
        public ?string $correlationId,
        public string $goal,
        public ?string $description,
        public ?array $requiredCourses,
        public int $progressPercentage,
        public string $status, // active, completed, paused, cancelled
        public CarbonImmutable $startDate,
        public CarbonImmutable $targetDate,
        public ?CarbonImmutable $completedDate,
        public ?int $mentorId,
        public ?string $notes,
        public ?array $tags,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $trainerId,
        string $goal,
        CarbonImmutable $targetDate,
        ?string $description = null,
        ?int $businessGroupId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            trainerId: $trainerId,
            uuid: '',
            correlationId: null,
            goal: $goal,
            description: $description,
            requiredCourses: null,
            progressPercentage: 0,
            status: 'active',
            startDate: CarbonImmutable::now(),
            targetDate: $targetDate,
            completedDate: null,
            mentorId: null,
            notes: null,
            tags: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateProgress(int $progressPercentage): self
    {
        return new self(
            ...get_object_vars($this),
            progressPercentage: min(100, max(0, $progressPercentage)),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function complete(): self
    {
        return new self(
            ...get_object_vars($this),
            progressPercentage: 100,
            status: 'completed',
            completedDate: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function pause(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'paused',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'cancelled',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->targetDate->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
