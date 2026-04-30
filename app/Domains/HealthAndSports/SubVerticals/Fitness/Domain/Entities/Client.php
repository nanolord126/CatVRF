<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Client
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $userId,
        public string $firstName,
        public string $lastName,
        public ?string $patronymic,
        public ?string $phone,
        public ?string $email,
        public ?CarbonImmutable $birthDate,
        public ?string $gender,
        public ?array $medicalRestrictions,
        public ?string $emergencyContact,
        public ?string $emergencyPhone,
        public ?array $goals,
        public ?string $fitnessLevel,
        public float $loyaltyPoints,
        public int $totalVisits,
        public ?string $photoUrl,
        public ?string $notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $userId,
        string $firstName,
        string $lastName,
        ?string $patronymic = null,
        ?string $phone = null,
        ?string $email = null,
        ?CarbonImmutable $birthDate = null,
        ?string $gender = null,
        ?array $medicalRestrictions = null,
        ?string $emergencyContact = null,
        ?string $emergencyPhone = null,
        ?array $goals = null,
        ?string $fitnessLevel = 'beginner',
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            userId: $userId,
            firstName: $firstName,
            lastName: $lastName,
            patronymic: $patronymic,
            phone: $phone,
            email: $email,
            birthDate: $birthDate,
            gender: $gender,
            medicalRestrictions: $medicalRestrictions,
            emergencyContact: $emergencyContact,
            emergencyPhone: $emergencyPhone,
            goals: $goals,
            fitnessLevel: $fitnessLevel,
            loyaltyPoints: 0.0,
            totalVisits: 0,
            photoUrl: null,
            notes: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s %s', $this->lastName, $this->firstName, $this->patronymic ?? ''));
    }

    public function getAge(): ?int
    {
        return $this->birthDate ? $this->birthDate->age : null;
    }

    public function hasMedicalRestriction(string $restriction): bool
    {
        return in_array($restriction, $this->medicalRestrictions ?? [], true);
    }

    public function addLoyaltyPoints(float $points): self
    {
        return new self(
            ...get_object_vars($this),
            loyaltyPoints: $this->loyaltyPoints + $points,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function incrementVisits(): self
    {
        return new self(
            ...get_object_vars($this),
            totalVisits: $this->totalVisits + 1,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function hasGoal(string $goal): bool
    {
        return in_array($goal, $this->goals ?? [], true);
    }

    public function addGoal(string $goal): self
    {
        $goals = $this->goals ?? [];
        if (!in_array($goal, $goals, true)) {
            $goals[] = $goal;
        }

        return new self(
            ...get_object_vars($this),
            goals: $goals,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
