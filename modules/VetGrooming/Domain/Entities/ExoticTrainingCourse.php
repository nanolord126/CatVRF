<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Carbon\CarbonImmutable;

final readonly class ExoticTrainingCourse
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $title,
        public string $description,
        public ExoticCategory $targetCategory,
        public ?string $targetSubcategory,
        public ExoticGroup $targetGroup,
        public CertificationLevel $targetLevel,
        public float $durationHours,
        public bool $isMandatory,
        public bool $requiresPracticalExam,
        public ?string $videoUrl,
        public ?array $modules,
        public ?array $learningObjectives,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $title,
        string $description,
        ExoticCategory $targetCategory,
        ?string $targetSubcategory,
        ExoticGroup $targetGroup,
        CertificationLevel $targetLevel,
        float $durationHours,
        bool $isMandatory = false,
        bool $requiresPracticalExam = true,
        ?string $videoUrl = null,
        ?array $modules = null,
        ?array $learningObjectives = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            title: $title,
            description: $description,
            targetCategory: $targetCategory,
            targetSubcategory: $targetSubcategory,
            targetGroup: $targetGroup,
            targetLevel: $targetLevel,
            durationHours: $durationHours,
            isMandatory: $isMandatory,
            requiresPracticalExam: $requiresPracticalExam,
            videoUrl: $videoUrl,
            modules: $modules,
            learningObjectives: $learningObjectives,
            isActive: true,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            title: $this->title,
            description: $this->description,
            targetCategory: $this->targetCategory,
            targetSubcategory: $this->targetSubcategory,
            targetGroup: $this->targetGroup,
            targetLevel: $this->targetLevel,
            durationHours: $this->durationHours,
            isMandatory: $this->isMandatory,
            requiresPracticalExam: $this->requiresPracticalExam,
            videoUrl: $this->videoUrl,
            modules: $this->modules,
            learningObjectives: $this->learningObjectives,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isForCategory(ExoticCategory $category): bool
    {
        return $this->targetCategory === $category;
    }

    public function isForGroup(ExoticGroup $group): bool
    {
        return $this->targetGroup === $group;
    }

    public function isForLevel(CertificationLevel $level): bool
    {
        return $this->targetLevel === $level;
    }
}
