<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\ShiftId;
use Modules\CatCRM\Domain\Staff\ValueObjects\ShiftStatus;

/**
 * Shift — Смена сотрудника
 * 
 * Readonly DDD entity для представления смены
 */
final readonly class Shift
{
    public function __construct(
        public ShiftId $id,
        public int $tenantId,
        public int $employeeId,
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
        public ?string $location,
        public ShiftStatus $status,
        public ?float $latitude,
        public ?float $longitude,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function getDurationHours(): float
    {
        return $this->startTime->diffInHours($this->endTime);
    }

    public function isOvertime(): bool
    {
        return $this->getDurationHours() > 8;
    }

    public function isActive(): bool
    {
        return $this->status === ShiftStatus::InProgress;
    }
}
