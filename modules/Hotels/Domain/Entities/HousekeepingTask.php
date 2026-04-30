<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class HousekeepingTask
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public int $roomId,
        public string $uuid,
        public string $taskType,
        public string $priority,
        public string $status,
        public ?CarbonImmutable $scheduledFor,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $completedAt,
        public ?int $assignedTo,
        public ?int $completedBy,
        public ?array $checklist,
        public ?array $notes,
        public ?array $photosBefore,
        public ?array $photosAfter,
        public int $estimatedMinutes,
        public ?int $actualMinutes,
        public ?int $bookingItemId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        int $roomId,
        string $taskType = 'clean',
        string $priority = 'normal',
        ?CarbonImmutable $scheduledFor = null,
        int $estimatedMinutes = 30,
        ?int $assignedTo = null,
        ?int $bookingItemId = null,
        ?array $checklist = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            roomId: $roomId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            taskType: $taskType,
            priority: $priority,
            status: 'pending',
            scheduledFor: $scheduledFor,
            startedAt: null,
            completedAt: null,
            assignedTo: $assignedTo,
            completedBy: null,
            checklist: $checklist,
            notes: null,
            photosBefore: null,
            photosAfter: null,
            estimatedMinutes: $estimatedMinutes,
            actualMinutes: null,
            bookingItemId: $bookingItemId,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function start(int $assignedTo): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'in_progress',
            startedAt: CarbonImmutable::now(),
            assignedTo: $assignedTo,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function complete(int $completedBy, ?int $actualMinutes = null): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'completed',
            completedAt: CarbonImmutable::now(),
            completedBy: $completedBy,
            actualMinutes: $actualMinutes ?? $this->startedAt?->diffInMinutes(CarbonImmutable::now()),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function skip(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'skipped',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->scheduledFor && $this->scheduledFor->isPast();
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
