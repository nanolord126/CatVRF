<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\TrainingCourseId;

/**
 * TrainingCourse — Курс обучения
 * 
 * Readonly DDD entity для представления курса
 */
final readonly class TrainingCourse
{
    public function __construct(
        public TrainingCourseId $id,
        public int $tenantId,
        public string $title,
        public string $description,
        public string $category,
        public int $durationHours,
        public int $difficultyLevel, // 1-5
        public array $requiredSkills, // ID навыков
        public array $acquiredSkills, // ID навыков
        public int $pointsReward,
        public ?string $externalUrl,
        public bool $isActive,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}
}
