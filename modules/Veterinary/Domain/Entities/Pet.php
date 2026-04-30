<?php

declare(strict_types=1);

namespace Modules\Veterinary\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Pet
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $ownerId,
        public string $name,
        public string $species,
        public ?string $breed,
        public ?CarbonImmutable $birthDate,
        public ?string $gender,
        public ?float $weight,
        public ?string $medicalNotes,
        public ?array $vaccinationHistory,
        public ?string $chipNumber,
        public ?CarbonImmutable $chipInstalledAt,
        public ?string $passportNumber,
        public bool $isNeutered,
        public ?array $tags,
        public ?string $correlationId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function getAge(): ?int
    {
        if (!$this->birthDate) {
            return null;
        }

        return (int) $this->birthDate->diffInYears(CarbonImmutable::now());
    }
}
