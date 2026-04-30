<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Enums\AttendanceStatus;

final readonly class Attendance
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $bookingId,
        public int $clientId,
        public int $scheduleSlotId,
        public AttendanceStatus $status,
        public CarbonImmutable $checkInTime,
        public ?CarbonImmutable $checkOutTime,
        public ?int $durationMinutes,
        public ?string $notes,
        public ?int $trainerId,
        public ?array $performanceMetrics,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $bookingId,
        int $clientId,
        int $scheduleSlotId,
        AttendanceStatus $status = AttendanceStatus::PRESENT,
        ?int $trainerId = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            bookingId: $bookingId,
            clientId: $clientId,
            scheduleSlotId: $scheduleSlotId,
            status: $status,
            checkInTime: CarbonImmutable::now(),
            checkOutTime: null,
            durationMinutes: null,
            notes: $notes,
            trainerId: $trainerId,
            performanceMetrics: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function checkOut(): self
    {
        $duration = $this->checkInTime->diffInMinutes(CarbonImmutable::now());

        return new self(
            ...get_object_vars($this),
            checkOutTime: CarbonImmutable::now(),
            durationMinutes: $duration,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markLate(): self
    {
        return new self(
            ...get_object_vars($this),
            status: AttendanceStatus::LATE,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markExcused(?string $reason = null): self
    {
        return new self(
            ...get_object_vars($this),
            status: AttendanceStatus::EXCUSED,
            notes: $reason ?? $this->notes,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addPerformanceMetrics(array $metrics): self
    {
        return new self(
            ...get_object_vars($this),
            performanceMetrics: $metrics,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function countsAsVisit(): bool
    {
        return $this->status->countsAsVisit();
    }

    public function getDuration(): ?int
    {
        return $this->durationMinutes;
    }
}
