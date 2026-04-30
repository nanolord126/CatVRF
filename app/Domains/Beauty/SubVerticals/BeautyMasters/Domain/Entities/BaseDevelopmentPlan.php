<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

abstract readonly class BaseDevelopmentPlan
{
    public function __construct(
        public int $id,
        public int $masterId,
        public array $requiredCourses,
        public int $progressPercent,
        public ?\DateTimeImmutable $nextCertificationDate,
        public ?string $mentorNotes,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }

    public function isComplete(): bool
    {
        return $this->progressPercent >= 100;
    }
}
