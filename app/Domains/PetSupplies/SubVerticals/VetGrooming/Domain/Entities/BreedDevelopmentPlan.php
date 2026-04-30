<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class BreedDevelopmentPlan
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $masterId,
        public string $targetBreedGroup,
        public ?string $targetBreed,
        public string $targetLevel,
        public CarbonImmutable $planStartDate,
        public CarbonImmutable $targetCompletionDate,
        public int $trainingHoursCompleted,
        public int $practicalHoursCompleted,
        public int $requiredTrainingHours,
        public int $requiredPracticalHours,
        public float $progressPercentage,
        public string $status,
        public ?array $prerequisites,
        public bool $prerequisitesMet,
        public ?array $trainingModules,
        public ?array $practicalTasks,
        public ?int $mentorId,
        public ?string $goals,
        public ?string $obstacles,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $masterId,
        string $targetBreedGroup,
        string $targetLevel = 'certified',
        ?string $targetBreed = null,
        ?int $mentorId = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            masterId: $masterId,
            targetBreedGroup: $targetBreedGroup,
            targetBreed: $targetBreed,
            targetLevel: $targetLevel,
            planStartDate: CarbonImmutable::now(),
            targetCompletionDate: CarbonImmutable::now()->addMonths(6),
            trainingHoursCompleted: 0,
            practicalHoursCompleted: 0,
            requiredTrainingHours: 0,
            requiredPracticalHours: 0,
            progressPercentage: 0.0,
            status: 'planned',
            prerequisites: null,
            prerequisitesMet: false,
            trainingModules: null,
            practicalTasks: null,
            mentorId: $mentorId,
            goals: null,
            obstacles: null,
            correlationId: $correlationId,
            tags: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function start(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted,
            practicalHoursCompleted: $this->practicalHoursCompleted,
            requiredTrainingHours: $this->requiredTrainingHours,
            requiredPracticalHours: $this->requiredPracticalHours,
            progressPercentage: $this->progressPercentage,
            status: 'in_progress',
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisitesMet,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function markReadyForExam(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted,
            practicalHoursCompleted: $this->practicalHoursCompleted,
            requiredTrainingHours: $this->requiredTrainingHours,
            requiredPracticalHours: $this->requiredPracticalHours,
            progressPercentage: 100.0,
            status: 'ready_for_exam',
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisitesMet,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
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
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted,
            practicalHoursCompleted: $this->practicalHoursCompleted,
            requiredTrainingHours: $this->requiredTrainingHours,
            requiredPracticalHours: $this->requiredPracticalHours,
            progressPercentage: 100.0,
            status: 'completed',
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisitesMet,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function cancel(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted,
            practicalHoursCompleted: $this->practicalHoursCompleted,
            requiredTrainingHours: $this->requiredTrainingHours,
            requiredPracticalHours: $this->requiredPracticalHours,
            progressPercentage: $this->progressPercentage,
            status: 'cancelled',
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisitesMet,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addTrainingHours(int $hours): self
    {
        $newProgress = $this->calculateProgress(
            $this->trainingHoursCompleted + $hours,
            $this->practicalHoursCompleted,
            $this->requiredTrainingHours,
            $this->requiredPracticalHours,
        );

        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted + $hours,
            practicalHoursCompleted: $this->practicalHoursCompleted,
            requiredTrainingHours: $this->requiredTrainingHours,
            requiredPracticalHours: $this->requiredPracticalHours,
            progressPercentage: $newProgress,
            status: $this->status,
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisitesMet,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addPracticalHours(int $hours): self
    {
        $newProgress = $this->calculateProgress(
            $this->trainingHoursCompleted,
            $this->practicalHoursCompleted + $hours,
            $this->requiredTrainingHours,
            $this->requiredPracticalHours,
        );

        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted,
            practicalHoursCompleted: $this->practicalHoursCompleted + $hours,
            requiredTrainingHours: $this->requiredTrainingHours,
            requiredPracticalHours: $this->requiredPracticalHours,
            progressPercentage: $newProgress,
            status: $this->status,
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisitesMet,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function setRequirements(int $trainingHours, int $practicalHours): self
    {
        $newProgress = $this->calculateProgress(
            $this->trainingHoursCompleted,
            $this->practicalHoursCompleted,
            $trainingHours,
            $practicalHours,
        );

        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted,
            practicalHoursCompleted: $this->practicalHoursCompleted,
            requiredTrainingHours: $trainingHours,
            requiredPracticalHours: $practicalHours,
            progressPercentage: $newProgress,
            status: $this->status,
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisitesMet,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function markPrerequisitesMet(bool $met = true): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            targetBreedGroup: $this->targetBreedGroup,
            targetBreed: $this->targetBreed,
            targetLevel: $this->targetLevel,
            planStartDate: $this->planStartDate,
            targetCompletionDate: $this->targetCompletionDate,
            trainingHoursCompleted: $this->trainingHoursCompleted,
            practicalHoursCompleted: $this->practicalHoursCompleted,
            requiredTrainingHours: $this->requiredTrainingHours,
            requiredPracticalHours: $this->requiredPracticalHours,
            progressPercentage: $this->progressPercentage,
            status: $this->status,
            prerequisites: $this->prerequisites,
            prerequisitesMet: $met,
            trainingModules: $this->trainingModules,
            practicalTasks: $this->practicalTasks,
            mentorId: $this->mentorId,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    private function calculateProgress(
        int $trainingCompleted,
        int $practicalCompleted,
        int $trainingRequired,
        int $practicalRequired,
    ): float {
        if ($trainingRequired === 0 && $practicalRequired === 0) {
            return 0.0;
        }

        $trainingProgress = $trainingRequired > 0
            ? ($trainingCompleted / $trainingRequired) * 50
            : 0;
        $practicalProgress = $practicalRequired > 0
            ? ($practicalCompleted / $practicalRequired) * 50
            : 0;

        return round(min(100, $trainingProgress + $practicalProgress), 2);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isReadyForExam(): bool
    {
        return $this->status === 'ready_for_exam';
    }

    public function isOverdue(): bool
    {
        return $this->targetCompletionDate->isPast() && ! $this->isComplete();
    }

    public function hasMetRequirements(): bool
    {
        return $this->trainingHoursCompleted >= $this->requiredTrainingHours
            && $this->practicalHoursCompleted >= $this->requiredPracticalHours;
    }
}
