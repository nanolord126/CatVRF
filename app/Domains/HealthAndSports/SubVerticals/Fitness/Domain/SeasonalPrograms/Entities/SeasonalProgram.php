<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Entities;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramStatus;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramType;

final readonly class SeasonalProgram
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public SeasonalProgramType $type,
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public int $durationWeeks,
        public int $sessionsPerWeek,
        public float $price,
        public ?int $maxParticipants,
        public ?array $goals,
        public ?string $requirements,
        public SeasonalProgramStatus $status,
        public int $currentParticipants,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $durationWeeks,
        int $sessionsPerWeek,
        float $price,
        SeasonalProgramType $type = SeasonalProgramType::GROUP,
        ?int $maxParticipants = null,
        ?string $description = null,
        ?array $goals = null,
        ?string $requirements = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            type: $type,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            durationWeeks: $durationWeeks,
            sessionsPerWeek: $sessionsPerWeek,
            price: $price,
            maxParticipants: $maxParticipants,
            goals: $goals,
            requirements: $requirements,
            status: SeasonalProgramStatus::DRAFT,
            currentParticipants: 0,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function publish(): self
    {
        return new self(
            ...get_object_vars($this),
            status: SeasonalProgramStatus::ACTIVE,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function archive(): self
    {
        return new self(
            ...get_object_vars($this),
            status: SeasonalProgramStatus::ARCHIVED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(): self
    {
        return new self(
            ...get_object_vars($this),
            status: SeasonalProgramStatus::CANCELLED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function incrementParticipants(): self
    {
        $newCount = $this->currentParticipants + 1;
        $status = $this->maxParticipants !== null && $newCount >= $this->maxParticipants
            ? SeasonalProgramStatus::FULL
            : $this->status;

        return new self(
            ...get_object_vars($this),
            currentParticipants: $newCount,
            status: $status,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function decrementParticipants(): self
    {
        return new self(
            ...get_object_vars($this),
            currentParticipants: max(0, $this->currentParticipants - 1),
            status: $this->status === SeasonalProgramStatus::FULL ? SeasonalProgramStatus::ACTIVE : $this->status,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function hasAvailableSlots(): bool
    {
        if ($this->maxParticipants === null) {
            return true;
        }

        return $this->currentParticipants < $this->maxParticipants;
    }

    public function getOccupancyRate(): float
    {
        if ($this->maxParticipants === null || $this->maxParticipants === 0) {
            return 0.0;
        }

        return ($this->currentParticipants / $this->maxParticipants) * 100;
    }

    public function isUpcoming(): bool
    {
        return $this->periodStart->isFuture();
    }

    public function isOngoing(): bool
    {
        $now = CarbonImmutable::now();
        return $this->periodStart->isPast() && $this->periodEnd->isFuture();
    }

    public function isPast(): bool
    {
        return $this->periodEnd->isPast();
    }
}
