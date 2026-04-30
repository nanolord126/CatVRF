<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

use Modules\BeautyMasters\Domain\Entities\CertificationLevel;

final readonly class CertificationTestResult
{
    public function __construct(
        public int $id,
        public int $masterId,
        public string $testName,
        public string $vertical, // 'beauty', 'makeup', 'brow', 'lash'
        public int $theoryScore,
        public int $practiceScore,
        public int $totalScore,
        public bool $passed,
        public ?CertificationLevel $awardedLevel,
        public ?array $practicalWorkPhotos,
        public ?string $feedback,
        public \DateTimeImmutable $completedAt,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public function hasPassed(): bool
    {
        return $this->passed;
    }
}
