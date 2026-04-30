<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Entities;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\EnrollmentStatus;

final readonly class ClientProgramEnrollment
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $clientId,
        public int $seasonalProgramId,
        public CarbonImmutable $startDate,
        public float $progressPercent,
        public EnrollmentStatus $status,
        public ?array $initialMetrics,
        public ?array $finalMetrics,
        public ?string $notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $clientId,
        int $seasonalProgramId,
        CarbonImmutable $startDate,
        ?array $initialMetrics = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            clientId: $clientId,
            seasonalProgramId: $seasonalProgramId,
            startDate: $startDate,
            progressPercent: 0.0,
            status: EnrollmentStatus::ACTIVE,
            initialMetrics: $initialMetrics,
            finalMetrics: null,
            notes: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateProgress(float $progressPercent): self
    {
        $clampedProgress = max(0.0, min(100.0, $progressPercent));
        $status = $clampedProgress >= 100.0 ? EnrollmentStatus::COMPLETED : $this->status;

        return new self(
            ...get_object_vars($this),
            progressPercent: $clampedProgress,
            status: $status,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function pause(): self
    {
        return new self(
            ...get_object_vars($this),
            status: EnrollmentStatus::PAUSED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function resume(): self
    {
        return new self(
            ...get_object_vars($this),
            status: EnrollmentStatus::ACTIVE,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function drop(?string $reason = null): self
    {
        return new self(
            ...get_object_vars($this),
            status: EnrollmentStatus::DROPPED,
            notes: $reason ?? $this->notes,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function complete(?array $finalMetrics = null): self
    {
        return new self(
            ...get_object_vars($this),
            progressPercent: 100.0,
            status: EnrollmentStatus::COMPLETED,
            finalMetrics: $finalMetrics ?? $this->finalMetrics,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isComplete(): bool
    {
        return $this->status === EnrollmentStatus::COMPLETED;
    }

    public function isActive(): bool
    {
        return $this->status === EnrollmentStatus::ACTIVE;
    }
}
