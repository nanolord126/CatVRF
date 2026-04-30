<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainingCourse
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public string $title,
        public ?string $description,
        public string $category,
        public string $professionType,
        public ?string $specialization,
        public int $durationHours,
        public ?string $requiredForLevel,
        public bool $isMandatory,
        public ?array $mandatoryForSpecializations,
        public ?array $mandatoryForBreeds,
        public bool $certificateIssued,
        public ?string $certificateTemplate,
        public ?array $learningObjectives,
        public ?array $prerequisites,
        public bool $isActive,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $title,
        string $category,
        string $professionType,
        int $durationHours = 0,
        ?string $description = null,
        ?string $specialization = null,
        ?string $requiredForLevel = null,
        bool $isMandatory = false,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            title: $title,
            description: $description,
            category: $category,
            professionType: $professionType,
            specialization: $specialization,
            durationHours: $durationHours,
            requiredForLevel: $requiredForLevel,
            isMandatory: $isMandatory,
            mandatoryForSpecializations: null,
            mandatoryForBreeds: null,
            certificateIssued: false,
            certificateTemplate: null,
            learningObjectives: null,
            prerequisites: null,
            isActive: true,
            correlationId: $correlationId,
            tags: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            title: $this->title,
            description: $this->description,
            category: $this->category,
            professionType: $this->professionType,
            specialization: $this->specialization,
            durationHours: $this->durationHours,
            requiredForLevel: $this->requiredForLevel,
            isMandatory: $this->isMandatory,
            mandatoryForSpecializations: $this->mandatoryForSpecializations,
            mandatoryForBreeds: $this->mandatoryForBreeds,
            certificateIssued: $this->certificateIssued,
            certificateTemplate: $this->certificateTemplate,
            learningObjectives: $this->learningObjectives,
            prerequisites: $this->prerequisites,
            isActive: false,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function updateDetails(
        ?string $title = null,
        ?string $description = null,
        ?int $durationHours = null,
        ?string $specialization = null,
    ): self {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            title: $title ?? $this->title,
            description: $description ?? $this->description,
            category: $this->category,
            professionType: $this->professionType,
            specialization: $specialization ?? $this->specialization,
            durationHours: $durationHours ?? $this->durationHours,
            requiredForLevel: $this->requiredForLevel,
            isMandatory: $this->isMandatory,
            mandatoryForSpecializations: $this->mandatoryForSpecializations,
            mandatoryForBreeds: $this->mandatoryForBreeds,
            certificateIssued: $this->certificateIssued,
            certificateTemplate: $this->certificateTemplate,
            learningObjectives: $this->learningObjectives,
            prerequisites: $this->prerequisites,
            isActive: $this->isActive,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function setMandatoryFor(?array $specializations = null, ?array $breeds = null): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            title: $this->title,
            description: $this->description,
            category: $this->category,
            professionType: $this->professionType,
            specialization: $this->specialization,
            durationHours: $this->durationHours,
            requiredForLevel: $this->requiredForLevel,
            isMandatory: true,
            mandatoryForSpecializations: $specializations,
            mandatoryForBreeds: $breeds,
            certificateIssued: $this->certificateIssued,
            certificateTemplate: $this->certificateTemplate,
            learningObjectives: $this->learningObjectives,
            prerequisites: $this->prerequisites,
            isActive: $this->isActive,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function isForProfession(string $professionType): bool
    {
        return $this->professionType === 'both' || $this->professionType === $professionType;
    }

    public function isMandatoryForSpecialization(string $specialization): bool
    {
        if (! $this->isMandatory || empty($this->mandatoryForSpecializations)) {
            return false;
        }

        return in_array($specialization, $this->mandatoryForSpecializations, true);
    }

    public function isMandatoryForBreed(string $breed): bool
    {
        if (! $this->isMandatory || empty($this->mandatoryForBreeds)) {
            return false;
        }

        return in_array($breed, $this->mandatoryForBreeds, true);
    }

    public function requiresLevel(string $level): bool
    {
        if ($this->requiredForLevel === null || $this->requiredForLevel === 'all') {
            return true;
        }

        $levels = ['junior', 'intermediate', 'senior', 'expert', 'master'];
        $requiredIndex = array_search($this->requiredForLevel, $levels);
        $currentLevelIndex = array_search($level, $levels);

        return $currentLevelIndex >= $requiredIndex;
    }
}
