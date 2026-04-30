<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class SkillMatrix
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $masterId,
        public string $skill,
        public ?string $skillCategory,
        public string $proficiencyLevel,
        public ?CarbonImmutable $assessedDate,
        public ?int $assessedBy,
        public ?string $assessmentNotes,
        public ?array $evidence,
        public int $practiceHours,
        public ?int $trainingCompletionId,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $masterId,
        string $skill,
        string $proficiencyLevel = 'beginner',
        ?string $skillCategory = null,
        ?int $trainingCompletionId = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            masterId: $masterId,
            skill: $skill,
            skillCategory: $skillCategory,
            proficiencyLevel: $proficiencyLevel,
            assessedDate: CarbonImmutable::now(),
            assessedBy: null,
            assessmentNotes: null,
            evidence: null,
            practiceHours: 0,
            trainingCompletionId: $trainingCompletionId,
            correlationId: $correlationId,
            tags: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function updateProficiency(string $proficiencyLevel, ?int $assessedBy = null): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            skill: $this->skill,
            skillCategory: $this->skillCategory,
            proficiencyLevel: $proficiencyLevel,
            assessedDate: CarbonImmutable::now(),
            assessedBy: $assessedBy ?? $this->assessedBy,
            assessmentNotes: $this->assessmentNotes,
            evidence: $this->evidence,
            practiceHours: $this->practiceHours,
            trainingCompletionId: $this->trainingCompletionId,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addPracticeHours(int $hours): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            skill: $this->skill,
            skillCategory: $this->skillCategory,
            proficiencyLevel: $this->proficiencyLevel,
            assessedDate: $this->assessedDate,
            assessedBy: $this->assessedBy,
            assessmentNotes: $this->assessmentNotes,
            evidence: $this->evidence,
            practiceHours: $this->practiceHours + $hours,
            trainingCompletionId: $this->trainingCompletionId,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addEvidence(array $evidence): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            skill: $this->skill,
            skillCategory: $this->skillCategory,
            proficiencyLevel: $this->proficiencyLevel,
            assessedDate: $this->assessedDate,
            assessedBy: $this->assessedBy,
            assessmentNotes: $this->assessmentNotes,
            evidence: $evidence,
            practiceHours: $this->practiceHours,
            trainingCompletionId: $this->trainingCompletionId,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addAssessmentNotes(string $notes): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            skill: $this->skill,
            skillCategory: $this->skillCategory,
            proficiencyLevel: $this->proficiencyLevel,
            assessedDate: $this->assessedDate,
            assessedBy: $this->assessedBy,
            assessmentNotes: $notes,
            evidence: $this->evidence,
            practiceHours: $this->practiceHours,
            trainingCompletionId: $this->trainingCompletionId,
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

    public function isExpert(): bool
    {
        return $this->proficiencyLevel === 'expert';
    }

    public function isAdvanced(): bool
    {
        return $this->isAtLeast('advanced');
    }

    public function hasMinimumPracticeHours(int $hours): bool
    {
        return $this->practiceHours >= $hours;
    }
}
