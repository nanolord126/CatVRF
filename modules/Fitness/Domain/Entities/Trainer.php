<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Trainer
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $userId,
        public string $firstName,
        public string $lastName,
        public ?string $patronymic,
        public ?string $specialization,
        public ?array $certifications,
        public float $rating,
        public int $totalSessions,
        public ?string $bio,
        public ?string $photoUrl,
        public ?array $workingHours,
        public float $hourlyRate,
        public bool $isAvailable,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $userId,
        string $firstName,
        string $lastName,
        ?string $patronymic = null,
        ?string $specialization = null,
        ?array $certifications = null,
        ?string $bio = null,
        ?string $photoUrl = null,
        ?array $workingHours = null,
        float $hourlyRate = 0.0,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            userId: $userId,
            firstName: $firstName,
            lastName: $lastName,
            patronymic: $patronymic,
            specialization: $specialization,
            certifications: $certifications,
            rating: 0.0,
            totalSessions: 0,
            bio: $bio,
            photoUrl: $photoUrl,
            workingHours: $workingHours,
            hourlyRate: $hourlyRate,
            isAvailable: true,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateRating(float $newRating, int $totalSessions): self
    {
        return new self(
            ...get_object_vars($this),
            rating: $newRating,
            totalSessions: $totalSessions,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function setAvailability(bool $available): self
    {
        return new self(
            ...get_object_vars($this),
            isAvailable: $available,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s %s', $this->lastName, $this->firstName, $this->patronymic ?? ''));
    }

    public function hasCertification(string $certification): bool
    {
        return in_array($certification, $this->certifications ?? [], true);
    }
}
