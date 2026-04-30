<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class CertificationTestResult
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public string $uuid,
        public ?string $correlationId,
        public string $testName,
        public string $testType, // internal | external
        public ?int $certificationId,
        public ?int $theoryScore,
        public ?int $practiceScore,
        public ?int $totalScore,
        public bool $passed,
        public ?array $answers,
        public ?string $feedback,
        public ?int $evaluatorId,
        public ?CarbonImmutable $evaluatedAt,
        public CarbonImmutable $completedAt,
        public ?array $tags,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $trainerId,
        string $testName,
        string $testType = 'internal',
        ?int $certificationId = null,
        ?int $businessGroupId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            trainerId: $trainerId,
            uuid: '',
            correlationId: null,
            testName: $testName,
            testType: $testType,
            certificationId: $certificationId,
            theoryScore: null,
            practiceScore: null,
            totalScore: null,
            passed: false,
            answers: null,
            feedback: null,
            evaluatorId: null,
            evaluatedAt: null,
            completedAt: CarbonImmutable::now(),
            tags: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function evaluate(int $theoryScore, int $practiceScore, int $evaluatorId, ?string $feedback = null): self
    {
        $totalScore = (int) round(($theoryScore + $practiceScore) / 2);
        $passed = $totalScore >= 70; // Pass threshold

        return new self(
            ...get_object_vars($this),
            theoryScore: $theoryScore,
            practiceScore: $practiceScore,
            totalScore: $totalScore,
            passed: $passed,
            feedback: $feedback,
            evaluatorId: $evaluatorId,
            evaluatedAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isExcellent(): bool
    {
        return $this->passed && ($this->totalScore ?? 0) >= 90;
    }

    public function isFailed(): bool
    {
        return !$this->passed;
    }
}
