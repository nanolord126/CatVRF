<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Enums\MembershipStatus;
use Modules\Fitness\Domain\Enums\MembershipType;

final readonly class Membership
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $clientId,
        public MembershipType $type,
        public MembershipStatus $status,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public ?int $remainingVisits,
        public ?int $totalVisits,
        public float $price,
        public bool $allowFreeze,
        public int $freezeDaysUsed,
        public int $maxFreezeDays,
        public ?CarbonImmutable $frozenAt,
        public ?CarbonImmutable $unfrozenAt,
        public ?string $notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $clientId,
        MembershipType $type,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        float $price,
        ?int $remainingVisits = null,
        ?int $totalVisits = null,
        bool $allowFreeze = true,
        int $maxFreezeDays = 30,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            clientId: $clientId,
            type: $type,
            status: MembershipStatus::ACTIVE,
            startDate: $startDate,
            endDate: $endDate,
            remainingVisits: $remainingVisits ?? $totalVisits,
            totalVisits: $totalVisits,
            price: $price,
            allowFreeze: $allowFreeze,
            freezeDaysUsed: 0,
            maxFreezeDays: $maxFreezeDays,
            frozenAt: null,
            unfrozenAt: null,
            notes: $notes,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === MembershipStatus::ACTIVE
            && $this->endDate->isFuture()
            && ($this->remainingVisits === null || $this->remainingVisits > 0);
    }

    public function isExpired(): bool
    {
        return $this->endDate->isPast()
            || ($this->remainingVisits !== null && $this->remainingVisits <= 0);
    }

    public function canFreeze(): bool
    {
        return $this->allowFreeze
            && $this->status === MembershipStatus::ACTIVE
            && $this->freezeDaysUsed < $this->maxFreezeDays;
    }

    public function freeze(): self
    {
        return new self(
            ...get_object_vars($this),
            status: MembershipStatus::FROZEN,
            frozenAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function unfreeze(): self
    {
        $newEndDate = $this->endDate->addDays($this->freezeDaysUsed);

        return new self(
            ...get_object_vars($this),
            status: MembershipStatus::ACTIVE,
            unfrozenAt: CarbonImmutable::now(),
            endDate: $newEndDate,
            freezeDaysUsed: 0,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function useVisit(): self
    {
        if ($this->remainingVisits === null) {
            return new self(
                ...get_object_vars($this),
                updatedAt: CarbonImmutable::now(),
            );
        }

        return new self(
            ...get_object_vars($this),
            remainingVisits: max(0, $this->remainingVisits - 1),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(): self
    {
        return new self(
            ...get_object_vars($this),
            status: MembershipStatus::CANCELLED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getDaysRemaining(): int
    {
        return max(0, $this->endDate->diffInDays(CarbonImmutable::now()));
    }

    public function getVisitsRemaining(): ?int
    {
        return $this->remainingVisits;
    }
}
