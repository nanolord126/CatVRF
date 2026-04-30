<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class ProfessionalDevelopmentPlan
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $masterId,
        public string $professionType,
        public string $currentLevel,
        public string $targetLevel,
        public CarbonImmutable $planStartDate,
        public CarbonImmutable $planEndDate,
        public CarbonImmutable $nextReviewDate,
        public int $totalCoursesRequired,
        public int $coursesCompleted,
        public float $completionPercentage,
        public ?float $effectivenessScore,
        public ?float $developmentScore,
        public string $status,
        public ?string $careerGoals,
        public ?string $skillGaps,
        public ?array $recommendedCourses,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $masterId,
        string $professionType,
        CarbonImmutable $planStartDate,
        CarbonImmutable $planEndDate,
        string $currentLevel = 'junior',
        string $targetLevel = 'intermediate',
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            masterId: $masterId,
            professionType: $professionType,
            currentLevel: $currentLevel,
            targetLevel: $targetLevel,
            planStartDate: $planStartDate,
            planEndDate: $planEndDate,
            nextReviewDate: $planStartDate->addMonths(3),
            totalCoursesRequired: 0,
            coursesCompleted: 0,
            completionPercentage: 0.0,
            effectivenessScore: null,
            developmentScore: null,
            status: 'draft',
            careerGoals: null,
            skillGaps: null,
            recommendedCourses: null,
            correlationId: $correlationId,
            tags: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            professionType: $this->professionType,
            currentLevel: $this->currentLevel,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            planEndDate: $this->planEndDate,
            nextReviewDate: $this->nextReviewDate,
            totalCoursesRequired: $this->totalCoursesRequired,
            coursesCompleted: $this->coursesCompleted,
            completionPercentage: $this->completionPercentage,
            effectivenessScore: $this->effectivenessScore,
            developmentScore: $this->developmentScore,
            status: 'active',
            careerGoals: $this->careerGoals,
            skillGaps: $this->skillGaps,
            recommendedCourses: $this->recommendedCourses,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function complete(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            professionType: $this->professionType,
            currentLevel: $this->targetLevel,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            planEndDate: $this->planEndDate,
            nextReviewDate: $this->nextReviewDate,
            totalCoursesRequired: $this->totalCoursesRequired,
            coursesCompleted: $this->coursesCompleted,
            completionPercentage: 100.0,
            effectivenessScore: $this->effectivenessScore,
            developmentScore: $this->developmentScore,
            status: 'completed',
            careerGoals: $this->careerGoals,
            skillGaps: $this->skillGaps,
            recommendedCourses: $this->recommendedCourses,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function updateProgress(int $coursesCompleted, int $totalCoursesRequired): self
    {
        $completionPercentage = $totalCoursesRequired > 0
            ? round(($coursesCompleted / $totalCoursesRequired) * 100, 2)
            : 0.0;

        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            professionType: $this->professionType,
            currentLevel: $this->currentLevel,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            planEndDate: $this->planEndDate,
            nextReviewDate: $this->nextReviewDate,
            totalCoursesRequired: $totalCoursesRequired,
            coursesCompleted: $coursesCompleted,
            completionPercentage: $completionPercentage,
            effectivenessScore: $this->effectivenessScore,
            developmentScore: $this->developmentScore,
            status: $this->status,
            careerGoals: $this->careerGoals,
            skillGaps: $this->skillGaps,
            recommendedCourses: $this->recommendedCourses,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function updateScores(?float $effectivenessScore, ?float $developmentScore): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            professionType: $this->professionType,
            currentLevel: $this->currentLevel,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            planEndDate: $this->planEndDate,
            nextReviewDate: $this->nextReviewDate,
            totalCoursesRequired: $this->totalCoursesRequired,
            coursesCompleted: $this->coursesCompleted,
            completionPercentage: $this->completionPercentage,
            effectivenessScore: $effectivenessScore,
            developmentScore: $developmentScore,
            status: $this->status,
            careerGoals: $this->careerGoals,
            skillGaps: $this->skillGaps,
            recommendedCourses: $this->recommendedCourses,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOverdue(): bool
    {
        return $this->planEndDate->isPast() && ! $this->isComplete();
    }

    public function isReviewDue(): bool
    {
        return $this->nextReviewDate->isPast() || $this->nextReviewDate->isToday();
    }

    public function hasLevelUp(): bool
    {
        $levels = ['junior', 'intermediate', 'senior', 'expert', 'master'];
        $currentIndex = array_search($this->currentLevel, $levels);
        $targetIndex = array_search($this->targetLevel, $levels);

        return $this->isComplete() && $targetIndex > $currentIndex;
    }
}
