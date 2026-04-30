<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Client
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public int $tenantId,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public ?string $email,
        public ?CarbonImmutable $birthDate,
        public ?string $preferences,
        public ?string $notes,
        public int $totalOrders,
        public float $totalSpent,
        public int $loyaltyPoints,
        public string $loyaltyTier,
        public ?CarbonImmutable $lastOrderDate,
        public bool $isSubscribed,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $firstName,
        string $lastName,
        string $phone,
        ?string $email = null,
        ?int $userId = null,
    ): self {
        return new self(
            id: 0,
            userId: $userId,
            tenantId: $tenantId,
            firstName: $firstName,
            lastName: $lastName,
            phone: $phone,
            email: $email,
            birthDate: null,
            preferences: null,
            notes: null,
            totalOrders: 0,
            totalSpent: 0.0,
            loyaltyPoints: 0,
            loyaltyTier: 'bronze',
            lastOrderDate: null,
            isSubscribed: false,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function isLoyal(): bool
    {
        return $this->totalOrders >= 3;
    }

    public function isVip(): bool
    {
        return in_array($this->loyaltyTier, ['gold', 'platinum']);
    }

    public function addOrder(float $amount): self
    {
        $pointsEarned = (int) floor($amount / 100); // 1 point per 100 RUB
        $newPoints = $this->loyaltyPoints + $pointsEarned;
        $newTier = $this->calculateLoyaltyTier($this->totalOrders + 1, $this->totalSpent + $amount);

        return new self(
            id: $this->id,
            userId: $this->userId,
            tenantId: $this->tenantId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            email: $this->email,
            birthDate: $this->birthDate,
            preferences: $this->preferences,
            notes: $this->notes,
            totalOrders: $this->totalOrders + 1,
            totalSpent: $this->totalSpent + $amount,
            loyaltyPoints: $newPoints,
            loyaltyTier: $newTier,
            lastOrderDate: CarbonImmutable::now(),
            isSubscribed: $this->isSubscribed,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    private function calculateLoyaltyTier(int $orders, float $spent): string
    {
        if ($orders >= 20 || $spent >= 50000) {
            return 'platinum';
        }

        if ($orders >= 10 || $spent >= 20000) {
            return 'gold';
        }

        if ($orders >= 5 || $spent >= 10000) {
            return 'silver';
        }

        return 'bronze';
    }

    public function hasBirthdaySoon(int $days = 7): bool
    {
        if (!$this->birthDate) {
            return false;
        }

        $birthdayThisYear = $this->birthDate->setYear(CarbonImmutable::now()->year);
        $daysUntilBirthday = $birthdayThisYear->diffInDays(CarbonImmutable::now(), false);

        return $daysUntilBirthday >= 0 && $daysUntilBirthday <= $days;
    }
}
