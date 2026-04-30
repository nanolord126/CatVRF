<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Guest
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $userId,
        public string $uuid,
        public string $firstName,
        public string $lastName,
        public ?string $patronymic,
        public ?string $email,
        public string $phone,
        public ?string $passportNumber,
        public ?CarbonImmutable $passportExpiry,
        public string $nationality,
        public ?CarbonImmutable $dateOfBirth,
        public ?string $gender,
        public ?array $preferences,
        public ?array $vipStatus,
        public int $loyaltyPoints,
        public ?int $loyaltyTierId,
        public int $totalStays,
        public int $totalNights,
        public float $totalSpent,
        public bool $isBlacklisted,
        public ?string $blacklistReason,
        public ?array $notes,
        public ?CarbonImmutable $firstStayAt,
        public ?CarbonImmutable $lastStayAt,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $firstName,
        string $lastName,
        string $phone,
        ?int $userId = null,
        ?string $patronymic = null,
        ?string $email = null,
        ?string $passportNumber = null,
        ?CarbonImmutable $passportExpiry = null,
        string $nationality = 'RUS',
        ?CarbonImmutable $dateOfBirth = null,
        ?string $gender = null,
        ?array $preferences = null,
        ?array $vipStatus = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            userId: $userId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            firstName: $firstName,
            lastName: $lastName,
            patronymic: $patronymic,
            email: $email,
            phone: $phone,
            passportNumber: $passportNumber,
            passportExpiry: $passportExpiry,
            nationality: $nationality,
            dateOfBirth: $dateOfBirth,
            gender: $gender,
            preferences: $preferences,
            vipStatus: $vipStatus,
            loyaltyPoints: 0,
            loyaltyTierId: null,
            totalStays: 0,
            totalNights: 0,
            totalSpent: 0.0,
            isBlacklisted: false,
            blacklistReason: null,
            notes: null,
            firstStayAt: null,
            lastStayAt: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addLoyaltyPoints(int $points): self
    {
        return new self(
            ...get_object_vars($this),
            loyaltyPoints: $this->loyaltyPoints + $points,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function recordStay(int $nights, float $amount): self
    {
        $firstStayAt = $this->firstStayAt ?? CarbonImmutable::now();
        
        return new self(
            ...get_object_vars($this),
            totalStays: $this->totalStays + 1,
            totalNights: $this->totalNights + $nights,
            totalSpent: $this->totalSpent + $amount,
            firstStayAt: $firstStayAt,
            lastStayAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function blacklist(string $reason): self
    {
        return new self(
            ...get_object_vars($this),
            isBlacklisted: true,
            blacklistReason: $reason,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function unblacklist(): self
    {
        return new self(
            ...get_object_vars($this),
            isBlacklisted: false,
            blacklistReason: null,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isVip(): bool
    {
        return $this->vipStatus !== null && isset($this->vipStatus['level']) && $this->vipStatus['level'] !== 'regular';
    }

    public function getFullName(): string
    {
        $name = trim($this->firstName . ' ' . $this->lastName);
        if ($this->patronymic) {
            $name .= ' ' . $this->patronymic;
        }
        return $name;
    }
}
