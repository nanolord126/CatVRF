<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Entities;

use Carbon\CarbonImmutable;

final readonly class EmployeeMembership
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $corporateEnrollmentId,
        public int $clientId,
        public int $remainingVisits,
        public ?int $totalVisits,
        public string $status,
        public CarbonImmutable $startDate,
        public ?CarbonImmutable $endDate,
        public ?string $notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $corporateEnrollmentId,
        int $clientId,
        int $totalVisits,
        CarbonImmutable $startDate,
        ?CarbonImmutable $endDate = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            corporateEnrollmentId: $corporateEnrollmentId,
            clientId: $clientId,
            remainingVisits: $totalVisits,
            totalVisits: $totalVisits,
            status: 'active',
            startDate: $startDate,
            endDate: $endDate,
            notes: $notes,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function useVisit(): self
    {
        if ($this->remainingVisits <= 0) {
            throw new \RuntimeException('No remaining visits');
        }

        return new self(
            ...get_object_vars($this),
            remainingVisits: $this->remainingVisits - 1,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addVisits(int $count): self
    {
        return new self(
            ...get_object_vars($this),
            remainingVisits: $this->remainingVisits + $count,
            totalVisits: ($this->totalVisits ?? 0) + $count,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'inactive',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->remainingVisits > 0;
    }

    public function hasRemainingVisits(): bool
    {
        return $this->remainingVisits > 0;
    }

    public function getUsagePercentage(): float
    {
        if ($this->totalVisits === null || $this->totalVisits === 0) {
            return 0.0;
        }

        $used = $this->totalVisits - $this->remainingVisits;
        return ($used / $this->totalVisits) * 100;
    }
}
