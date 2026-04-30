<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Florist
{
    public function __construct(
        public int $id,
        public int $venueId,
        public ?int $userId,
        public int $tenantId,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public ?string $email,
        public ?array $workingHours,
        public ?float $hourlyRate,
        public ?string $specialization,
        public ?string $skills,
        public int $ordersCompleted,
        public float $averageRating,
        public int $totalRatingCount,
        public bool $isAvailable,
        public bool $isActive,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $venueId,
        int $tenantId,
        string $firstName,
        string $lastName,
        string $phone,
        ?string $email = null,
        ?int $userId = null,
    ): self {
        return new self(
            id: 0,
            venueId: $venueId,
            tenantId: $tenantId,
            userId: $userId,
            firstName: $firstName,
            lastName: $lastName,
            phone: $phone,
            email: $email,
            workingHours: null,
            hourlyRate: null,
            specialization: null,
            skills: null,
            ordersCompleted: 0,
            averageRating: 0.0,
            totalRatingCount: 0,
            isAvailable: true,
            isActive: true,
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

    public function isExperienced(): bool
    {
        return $this->ordersCompleted >= 50;
    }

    public function isTopRated(): bool
    {
        return $this->averageRating >= 4.5 && $this->totalRatingCount >= 10;
    }

    public function completeOrder(): self
    {
        return new self(
            id: $this->id,
            venueId: $this->venueId,
            userId: $this->userId,
            tenantId: $this->tenantId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            email: $this->email,
            workingHours: $this->workingHours,
            hourlyRate: $this->hourlyRate,
            specialization: $this->specialization,
            skills: $this->skills,
            ordersCompleted: $this->ordersCompleted + 1,
            averageRating: $this->averageRating,
            totalRatingCount: $this->totalRatingCount,
            isAvailable: $this->isAvailable,
            isActive: $this->isActive,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addRating(float $rating): self
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        $totalRating = $this->averageRating * $this->totalRatingCount;
        $newTotalRating = $totalRating + $rating;
        $newCount = $this->totalRatingCount + 1;
        $newAverage = $newTotalRating / $newCount;

        return new self(
            id: $this->id,
            venueId: $this->venueId,
            userId: $this->userId,
            tenantId: $this->tenantId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            email: $this->email,
            workingHours: $this->workingHours,
            hourlyRate: $this->hourlyRate,
            specialization: $this->specialization,
            skills: $this->skills,
            ordersCompleted: $this->ordersCompleted,
            averageRating: round($newAverage, 2),
            totalRatingCount: $newCount,
            isAvailable: $this->isAvailable,
            isActive: $this->isActive,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function setAvailable(bool $available): self
    {
        return new self(
            id: $this->id,
            venueId: $this->venueId,
            userId: $this->userId,
            tenantId: $this->tenantId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            email: $this->email,
            workingHours: $this->workingHours,
            hourlyRate: $this->hourlyRate,
            specialization: $this->specialization,
            skills: $this->skills,
            ordersCompleted: $this->ordersCompleted,
            averageRating: $this->averageRating,
            totalRatingCount: $this->totalRatingCount,
            isAvailable: $available,
            isActive: $this->isActive,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }
}
