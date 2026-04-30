<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class BreedSpecialization
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $masterId,
        public string $breedGroup,
        public string $proficiencyLevel,
        public int $totalGroomingsCompleted,
        public int $certificationsCount,
        public ?float $averageRating,
        public int $repeatClients,
        public bool $isActive,
        public ?string $notes,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $masterId,
        string $breedGroup,
        string $proficiencyLevel = 'beginner',
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            masterId: $masterId,
            breedGroup: $breedGroup,
            proficiencyLevel: $proficiencyLevel,
            totalGroomingsCompleted: 0,
            certificationsCount: 0,
            averageRating: null,
            repeatClients: 0,
            isActive: true,
            notes: null,
            correlationId: $correlationId,
            tags: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function incrementGroomings(int $count = 1): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            breedGroup: $this->breedGroup,
            proficiencyLevel: $this->proficiencyLevel,
            totalGroomingsCompleted: $this->totalGroomingsCompleted + $count,
            certificationsCount: $this->certificationsCount,
            averageRating: $this->averageRating,
            repeatClients: $this->repeatClients,
            isActive: $this->isActive,
            notes: $this->notes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function incrementCertifications(int $count = 1): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            breedGroup: $this->breedGroup,
            proficiencyLevel: $this->proficiencyLevel,
            totalGroomingsCompleted: $this->totalGroomingsCompleted,
            certificationsCount: $this->certificationsCount + $count,
            averageRating: $this->averageRating,
            repeatClients: $this->repeatClients,
            isActive: $this->isActive,
            notes: $this->notes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function updateRating(float $rating): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            breedGroup: $this->breedGroup,
            proficiencyLevel: $this->proficiencyLevel,
            totalGroomingsCompleted: $this->totalGroomingsCompleted,
            certificationsCount: $this->certificationsCount,
            averageRating: $rating,
            repeatClients: $this->repeatClients,
            isActive: $this->isActive,
            notes: $this->notes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function incrementRepeatClients(int $count = 1): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            breedGroup: $this->breedGroup,
            proficiencyLevel: $this->proficiencyLevel,
            totalGroomingsCompleted: $this->totalGroomingsCompleted,
            certificationsCount: $this->certificationsCount,
            averageRating: $this->averageRating,
            repeatClients: $this->repeatClients + $count,
            isActive: $this->isActive,
            notes: $this->notes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function updateProficiency(string $proficiencyLevel): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            breedGroup: $this->breedGroup,
            proficiencyLevel: $proficiencyLevel,
            totalGroomingsCompleted: $this->totalGroomingsCompleted,
            certificationsCount: $this->certificationsCount,
            averageRating: $this->averageRating,
            repeatClients: $this->repeatClients,
            isActive: $this->isActive,
            notes: $this->notes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            breedGroup: $this->breedGroup,
            proficiencyLevel: $this->proficiencyLevel,
            totalGroomingsCompleted: $this->totalGroomingsCompleted,
            certificationsCount: $this->certificationsCount,
            averageRating: $this->averageRating,
            repeatClients: $this->repeatClients,
            isActive: false,
            notes: $this->notes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function isAtLeast(string $level): bool
    {
        $levels = ['beginner', 'intermediate', 'advanced', 'expert'];
        $currentLevelIndex = array_search($this->proficiencyLevel, $levels);
        $requiredLevelIndex = array_search($level, $levels);

        return $currentLevelIndex >= $requiredLevelIndex;
    }

    public function hasMinimumGroomings(int $minGroomings): bool
    {
        return $this->totalGroomingsCompleted >= $minGroomings;
    }

    public function hasCertifications(): bool
    {
        return $this->certificationsCount > 0;
    }

    public function isExpert(): bool
    {
        return $this->proficiencyLevel === 'expert';
    }

    public function isAdvanced(): bool
    {
        return $this->isAtLeast('advanced');
    }
}
