<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Shift
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public int $userId,
        public string $uuid,
        public string $role,
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
        public string $status,
        public ?string $location,
        public ?array $assignedTasks,
        public ?array $breaks,
        public ?string $notes,
        public ?int $supervisorId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        int $userId,
        string $role,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
        ?string $location = null,
        ?int $supervisorId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            userId: $userId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            role: $role,
            startTime: $startTime,
            endTime: $endTime,
            status: 'scheduled',
            location: $location,
            assignedTasks: null,
            breaks: null,
            notes: null,
            supervisorId: $supervisorId,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function start(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'active',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function end(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'completed',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(string $reason): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'cancelled',
            notes: $this->notes ? $this->notes . ' | Cancelled: ' . $reason : 'Cancelled: ' . $reason,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getDurationHours(): float
    {
        return $this->startTime->diffInHours($this->endTime);
    }
}
